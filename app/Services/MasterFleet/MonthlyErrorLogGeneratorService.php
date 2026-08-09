<?php

namespace App\Services\MasterFleet;

use App\Models\FleetGoogleAccount;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class MonthlyErrorLogGeneratorService
{
    private const GOOGLE_FOLDER_MIME = 'application/vnd.google-apps.folder';
    private const GOOGLE_SHEET_MIME = 'application/vnd.google-apps.spreadsheet';
    private const APP_TYPE = 'simola_errorlog_monthly';

    public function __construct(
        private readonly MasterFleetGoogleWorkspaceService $workspaceService
    ) {
    }

    /**
     * Membuat atau memakai ulang satu spreadsheet Error Log untuk kombinasi
     * root folder + bulan. Jika file sudah ada, file tersebut tidak diduplikasi;
     * REKAP!B1 tetap disinkronkan ke bulan yang dipilih.
     *
     * @return array{
     *   created: bool,
     *   spreadsheet_id: string,
     *   spreadsheet_name: string,
     *   spreadsheet_url: string,
     *   root_folder_id: string,
     *   root_folder_name: string,
     *   month: string,
     *   month_label: string,
     *   period_cell: string,
     *   period_value: string,
     *   google_email: string
     * }
     */
    public function generate(
        User $user,
        Carbon $month,
        string $rootFolderInput
    ): array {
        $month = $month->copy()->startOfMonth();
        $monthKey = $month->format('Y-m');
        $rootFolderId = $this->extractDriveId($rootFolderInput);

        if ($rootFolderId === '') {
            throw new RuntimeException('Folder root Error Log belum diisi.');
        }

        $lock = Cache::lock(
            'simola:errorlog-monthly:' . sha1($rootFolderId . '|' . $monthKey),
            45
        );

        if (!$lock->get()) {
            throw new RuntimeException(
                'Generator untuk folder dan bulan tersebut sedang diproses. Coba kembali setelah proses aktif selesai.'
            );
        }

        try {
            return $this->generateInsideLock(
                $user,
                $month,
                $rootFolderId
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * Mengecek apakah spreadsheet untuk kombinasi root + bulan sudah tersedia,
     * tanpa membuat atau mengubah file apa pun.
     *
     * @return array{
     *   exists: bool,
     *   spreadsheet_id: string,
     *   spreadsheet_name: string,
     *   spreadsheet_url: string,
     *   root_folder_id: string,
     *   root_folder_name: string,
     *   month: string,
     *   month_label: string,
     *   expected_name: string,
     *   google_email: string
     * }
     */
    public function lookup(
        User $user,
        Carbon $month,
        string $rootFolderInput
    ): array {
        $month = $month->copy()->startOfMonth();
        $monthKey = $month->format('Y-m');
        $rootFolderId = $this->extractDriveId($rootFolderInput);

        if ($rootFolderId === '') {
            throw new RuntimeException('Folder root Error Log belum diisi atau format ID/URL tidak valid.');
        }

        $client = $this->authorizedK302Client($user);
        $drive = new GoogleDrive($client);

        $rootFolder = $drive->files->get(
            $rootFolderId,
            [
                'fields' => 'id,name,mimeType',
                'supportsAllDrives' => true,
            ]
        );

        if ($rootFolder->getMimeType() !== self::GOOGLE_FOLDER_MIME) {
            throw new RuntimeException('Root yang dipilih bukan folder Google Drive.');
        }

        $monthLabel = $this->indonesianMonthName((int) $month->month)
            . ' ' . $month->format('Y');
        $filePrefix = trim((string) config(
            'errorlog-monthly.file_prefix',
            'ERROR LOG SIMOLA'
        ));
        $fileName = trim($filePrefix . ' - ' . $monthLabel);

        $file = $this->findByRootAndMonth(
            $drive,
            $rootFolderId,
            $monthKey
        );

        if ($file === null) {
            $file = $this->findByExactName(
                $drive,
                $rootFolderId,
                $fileName
            );
        }

        // Kompatibilitas struktur Drive operasional SIMOLA:
        // ROOT / YYYY / NAMA_BULAN / spreadsheet.
        // v1.0-v1.1 hanya memeriksa spreadsheet tepat di bawah root sehingga
        // file lama di folder bulanan tidak terdeteksi.
        if ($file === null) {
            $nested = $this->findNestedMonthlySheet(
                $drive,
                $rootFolderId,
                $month,
                $fileName
            );
            $file = $nested['file'] ?? null;
        }

        $account = $this->k302Account($user);
        $spreadsheetId = $file !== null
            ? trim((string) $file->getId())
            : '';

        return [
            'exists' => $file !== null && $spreadsheetId !== '',
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_name' => $file !== null
                ? (string) ($file->getName() ?: $fileName)
                : '',
            'spreadsheet_url' => $spreadsheetId !== ''
                ? 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit'
                : '',
            'root_folder_id' => $rootFolderId,
            'root_folder_name' => (string) $rootFolder->getName(),
            'month' => $monthKey,
            'month_label' => $monthLabel,
            'expected_name' => $fileName,
            'google_email' => (string) ($account->google_email ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateInsideLock(
        User $user,
        Carbon $month,
        string $rootFolderId
    ): array {
        $client = $this->authorizedK302Client($user);
        $drive = new GoogleDrive($client);
        $sheets = new GoogleSheets($client);

        $rootFolder = $drive->files->get(
            $rootFolderId,
            [
                'fields' => 'id,name,mimeType',
                'supportsAllDrives' => true,
            ]
        );

        if ($rootFolder->getMimeType() !== self::GOOGLE_FOLDER_MIME) {
            throw new RuntimeException('Root yang dipilih bukan folder Google Drive.');
        }

        $templateSpreadsheetId = trim((string) config(
            'errorlog-monthly.template_spreadsheet_id'
        ));

        if ($templateSpreadsheetId === '') {
            throw new RuntimeException('Template spreadsheet Error Log belum dikonfigurasi.');
        }

        $template = $drive->files->get(
            $templateSpreadsheetId,
            [
                'fields' => 'id,name,mimeType',
                'supportsAllDrives' => true,
            ]
        );

        if ($template->getMimeType() !== self::GOOGLE_SHEET_MIME) {
            throw new RuntimeException('Template Error Log bukan Google Spreadsheet.');
        }

        $monthKey = $month->format('Y-m');
        $monthLabel = $this->indonesianMonthName((int) $month->month)
            . ' ' . $month->format('Y');
        $filePrefix = trim((string) config(
            'errorlog-monthly.file_prefix',
            'ERROR LOG SIMOLA'
        ));
        $fileName = trim($filePrefix . ' - ' . $monthLabel);

        $file = $this->findByRootAndMonth(
            $drive,
            $rootFolderId,
            $monthKey
        );

        if ($file === null) {
            // Fallback v1.0-v1.1: file langsung di root tanpa appProperties.
            $file = $this->findByExactName(
                $drive,
                $rootFolderId,
                $fileName
            );
        }

        // Struktur operasional yang sudah dipakai di Drive:
        // ROOT / YYYY / AGUSTUS / spreadsheet.
        if ($file === null) {
            $nested = $this->findNestedMonthlySheet(
                $drive,
                $rootFolderId,
                $month,
                $fileName
            );
            $file = $nested['file'] ?? null;
        }

        $created = false;

        if ($file === null) {
            // Mulai v1.1.1 file baru mengikuti struktur Drive existing:
            // root / tahun / bulan / spreadsheet.
            $targetFolderId = $this->resolveOrCreateMonthFolder(
                $drive,
                $rootFolderId,
                $month
            );

            $metadata = new DriveFile([
                'name' => $fileName,
                'parents' => [$targetFolderId],
                'appProperties' => [
                    // Anti-duplikat tetap berbasis LOGICAL ROOT + YYYY-MM,
                    // bukan parent fisik spreadsheet.
                    'simola_type' => self::APP_TYPE,
                    'simola_month' => $monthKey,
                    'simola_root' => $rootFolderId,
                    'simola_template' => $templateSpreadsheetId,
                ],
            ]);

            $file = $drive->files->copy(
                $templateSpreadsheetId,
                $metadata,
                [
                    'fields' => 'id,name,webViewLink,parents,appProperties',
                    'supportsAllDrives' => true,
                ]
            );
            $created = true;
        } else {
            // Pastikan file lama/nested diberi identitas anti-duplikat agar
            // pencarian berikutnya konsisten tanpa mengubah lokasi file.
            $drive->files->update(
                (string) $file->getId(),
                new DriveFile([
                    'appProperties' => [
                        'simola_type' => self::APP_TYPE,
                        'simola_month' => $monthKey,
                        'simola_root' => $rootFolderId,
                        'simola_template' => $templateSpreadsheetId,
                    ],
                ]),
                [
                    'fields' => 'id,name,webViewLink,parents,appProperties',
                    'supportsAllDrives' => true,
                ]
            );
        }

        $spreadsheetId = trim((string) $file->getId());
        if ($spreadsheetId === '') {
            throw new RuntimeException('Google Drive tidak mengembalikan ID spreadsheet hasil generator.');
        }

        $periodSheet = trim((string) config(
            'errorlog-monthly.rekap_sheet',
            'REKAP'
        ));
        $periodCell = strtoupper(trim((string) config(
            'errorlog-monthly.period_cell',
            'B1'
        )));

        if ($periodSheet === '' || $periodCell === '') {
            throw new RuntimeException('Konfigurasi sel periode Error Log tidak valid.');
        }

        // Isi sebagai tanggal hari pertama bulan (ISO) agar formula template dapat
        // memakai MONTH/YEAR, sementara format tampilan cell dari template tetap dipertahankan.
        $periodValue = $month->format('Y-m-d');
        $escapedSheet = str_replace("'", "''", $periodSheet);
        $range = "'{$escapedSheet}'!{$periodCell}";

        $valueRange = new ValueRange();
        $valueRange->setRange($range);
        $valueRange->setMajorDimension('ROWS');
        $valueRange->setValues([[$periodValue]]);

        try {
            $sheets->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $valueRange,
                ['valueInputOption' => 'USER_ENTERED']
            );
        } catch (\Throwable $exception) {
            // Jika copy baru gagal dikonfigurasi, hapus agar percobaan berikutnya
            // tidak terkunci pada file setengah jadi.
            if ($created) {
                try {
                    $drive->files->delete(
                        $spreadsheetId,
                        ['supportsAllDrives' => true]
                    );
                } catch (\Throwable) {
                    // Abaikan kegagalan cleanup dan tampilkan akar masalah Sheets.
                }
            }

            throw new RuntimeException(
                'Spreadsheet ditemukan/dibuat, tetapi gagal mengisi '
                . $periodSheet . '!' . $periodCell . ': '
                . $exception->getMessage(),
                0,
                $exception
            );
        }

        $account = $this->k302Account($user);

        return [
            'created' => $created,
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_name' => (string) ($file->getName() ?: $fileName),
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'
                . $spreadsheetId . '/edit',
            'root_folder_id' => $rootFolderId,
            'root_folder_name' => (string) $rootFolder->getName(),
            'month' => $monthKey,
            'month_label' => $monthLabel,
            'period_cell' => $periodSheet . '!' . $periodCell,
            'period_value' => $periodValue,
            'google_email' => (string) ($account->google_email ?? ''),
        ];
    }

    private function authorizedK302Client(User $user): GoogleClient
    {
        $account = $this->k302Account($user);
        $client = $this->workspaceService->authorizationClient();
        $token = $account->token_payload ?? [];

        if (!is_array($token) || $token === []) {
            throw new RuntimeException('Token OAuth K3-02 tidak tersedia. Hubungkan ulang akun K3-02.');
        }

        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $token['refresh_token'] ?? null;

            if (!is_string($refreshToken) || trim($refreshToken) === '') {
                throw new RuntimeException(
                    'Refresh token OAuth K3-02 tidak tersedia. Putuskan lalu hubungkan kembali akun K3-02.'
                );
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($newToken['error'])) {
                throw new RuntimeException(
                    'Token OAuth K3-02 gagal diperbarui: '
                    . ($newToken['error_description'] ?? $newToken['error'])
                );
            }

            $newToken['refresh_token'] = $refreshToken;
            $account->update([
                'token_payload' => $newToken,
                'last_refreshed_at' => now(),
            ]);
            $client->setAccessToken($newToken);
        }

        return $client;
    }

    private function k302Account(User $user): FleetGoogleAccount
    {
        $account = FleetGoogleAccount::query()
            ->where('user_id', $user->id)
            ->where('purpose', FleetGoogleAccount::PURPOSE_K302)
            ->first();

        if ($account === null) {
            throw new RuntimeException(
                'Akun OAuth K3-02 belum dihubungkan. Hubungkan K3-02 dari Google Workspace Master Fleet.'
            );
        }

        return $account;
    }

    private function findByRootAndMonth(
        GoogleDrive $drive,
        string $rootFolderId,
        string $monthKey
    ): ?DriveFile {
        $root = $this->escapeDriveQueryValue($rootFolderId);
        $month = $this->escapeDriveQueryValue($monthKey);
        $type = $this->escapeDriveQueryValue(self::APP_TYPE);

        $query = "'{$root}' in parents "
            . "and mimeType = '" . self::GOOGLE_SHEET_MIME . "' "
            . 'and trashed = false '
            . "and appProperties has { key='simola_type' and value='{$type}' } "
            . "and appProperties has { key='simola_month' and value='{$month}' }";

        $result = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'pageSize' => 10,
            'orderBy' => 'createdTime',
            'fields' => 'files(id,name,webViewLink,parents,appProperties,createdTime)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $files = $result->getFiles() ?? [];

        if (count($files) > 1) {
            throw new RuntimeException(
                'Terdeteksi lebih dari satu spreadsheet Error Log untuk root + bulan '
                . $monthKey . '. Rapikan duplikat di Google Drive sebelum melanjutkan.'
            );
        }

        return $files[0] ?? null;
    }

    private function findByExactName(
        GoogleDrive $drive,
        string $rootFolderId,
        string $fileName
    ): ?DriveFile {
        $root = $this->escapeDriveQueryValue($rootFolderId);
        $name = $this->escapeDriveQueryValue($fileName);

        $query = "'{$root}' in parents "
            . "and name = '{$name}' "
            . "and mimeType = '" . self::GOOGLE_SHEET_MIME . "' "
            . 'and trashed = false';

        $result = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'pageSize' => 10,
            'fields' => 'files(id,name,webViewLink,parents,appProperties,createdTime)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $files = $result->getFiles() ?? [];

        if (count($files) > 1) {
            throw new RuntimeException(
                'Nama spreadsheet bulan tersebut sudah memiliki lebih dari satu duplikat di root terpilih.'
            );
        }

        return $files[0] ?? null;
    }

    /**
     * Cari spreadsheet existing pada struktur root / YYYY / BULAN.
     * Tidak melakukan perubahan apa pun sehingga aman dipakai endpoint status.
     *
     * @return array{file:?DriveFile, year_folder_id:string, month_folder_id:string}
     */
    private function findNestedMonthlySheet(
        GoogleDrive $drive,
        string $rootFolderId,
        Carbon $month,
        string $expectedFileName
    ): array {
        $yearFolder = $this->findSingleFolderByName(
            $drive,
            $rootFolderId,
            $month->format('Y')
        );

        if ($yearFolder === null) {
            return [
                'file' => null,
                'year_folder_id' => '',
                'month_folder_id' => '',
            ];
        }

        $monthFolder = $this->findSingleFolderByName(
            $drive,
            (string) $yearFolder->getId(),
            $this->indonesianMonthName((int) $month->month)
        );

        if ($monthFolder === null) {
            return [
                'file' => null,
                'year_folder_id' => (string) $yearFolder->getId(),
                'month_folder_id' => '',
            ];
        }

        $monthFolderId = (string) $monthFolder->getId();
        $monthKey = $month->format('Y-m');

        // Prioritas 1: metadata SIMOLA pada parent bulanan.
        $file = $this->findByParentAndLogicalMonth(
            $drive,
            $monthFolderId,
            $rootFolderId,
            $monthKey
        );

        // Prioritas 2: nama file standar generator.
        if ($file === null) {
            $file = $this->findByExactName(
                $drive,
                $monthFolderId,
                $expectedFileName
            );
        }

        // Prioritas 3: kompatibilitas nama file operasional lama.
        // Ambil spreadsheet di folder bulan yang namanya jelas mengandung
        // kata ERROR dan LOG. Jangan menebak jika ada >1 kandidat.
        if ($file === null) {
            $file = $this->findLegacyErrorLogByName(
                $drive,
                $monthFolderId
            );
        }

        return [
            'file' => $file,
            'year_folder_id' => (string) $yearFolder->getId(),
            'month_folder_id' => $monthFolderId,
        ];
    }

    private function resolveOrCreateMonthFolder(
        GoogleDrive $drive,
        string $rootFolderId,
        Carbon $month
    ): string {
        $yearName = $month->format('Y');
        $yearFolder = $this->findSingleFolderByName(
            $drive,
            $rootFolderId,
            $yearName
        );

        if ($yearFolder === null) {
            $yearFolder = $drive->files->create(
                new DriveFile([
                    'name' => $yearName,
                    'mimeType' => self::GOOGLE_FOLDER_MIME,
                    'parents' => [$rootFolderId],
                ]),
                [
                    'fields' => 'id,name,mimeType,parents',
                    'supportsAllDrives' => true,
                ]
            );
        }

        $yearFolderId = trim((string) $yearFolder->getId());
        if ($yearFolderId === '') {
            throw new RuntimeException('Google Drive tidak mengembalikan ID folder tahun.');
        }

        $monthName = $this->indonesianMonthName((int) $month->month);
        $monthFolder = $this->findSingleFolderByName(
            $drive,
            $yearFolderId,
            $monthName
        );

        if ($monthFolder === null) {
            $monthFolder = $drive->files->create(
                new DriveFile([
                    'name' => $monthName,
                    'mimeType' => self::GOOGLE_FOLDER_MIME,
                    'parents' => [$yearFolderId],
                ]),
                [
                    'fields' => 'id,name,mimeType,parents',
                    'supportsAllDrives' => true,
                ]
            );
        }

        $monthFolderId = trim((string) $monthFolder->getId());
        if ($monthFolderId === '') {
            throw new RuntimeException('Google Drive tidak mengembalikan ID folder bulan.');
        }

        return $monthFolderId;
    }

    private function findSingleFolderByName(
        GoogleDrive $drive,
        string $parentFolderId,
        string $folderName
    ): ?DriveFile {
        $parent = $this->escapeDriveQueryValue($parentFolderId);
        $name = $this->escapeDriveQueryValue($folderName);

        $query = "'{$parent}' in parents "
            . "and name = '{$name}' "
            . "and mimeType = '" . self::GOOGLE_FOLDER_MIME . "' "
            . 'and trashed = false';

        $result = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'pageSize' => 10,
            'fields' => 'files(id,name,mimeType,parents,createdTime)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $folders = $result->getFiles() ?? [];

        if (count($folders) > 1) {
            throw new RuntimeException(
                'Terdeteksi lebih dari satu folder bernama "'
                . $folderName
                . '" pada lokasi yang sama. Rapikan duplikat folder terlebih dahulu.'
            );
        }

        return $folders[0] ?? null;
    }

    private function findByParentAndLogicalMonth(
        GoogleDrive $drive,
        string $parentFolderId,
        string $logicalRootFolderId,
        string $monthKey
    ): ?DriveFile {
        $parent = $this->escapeDriveQueryValue($parentFolderId);
        $root = $this->escapeDriveQueryValue($logicalRootFolderId);
        $month = $this->escapeDriveQueryValue($monthKey);
        $type = $this->escapeDriveQueryValue(self::APP_TYPE);

        $query = "'{$parent}' in parents "
            . "and mimeType = '" . self::GOOGLE_SHEET_MIME . "' "
            . 'and trashed = false '
            . "and appProperties has { key='simola_type' and value='{$type}' } "
            . "and appProperties has { key='simola_month' and value='{$month}' } "
            . "and appProperties has { key='simola_root' and value='{$root}' }";

        $result = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'pageSize' => 10,
            'orderBy' => 'createdTime',
            'fields' => 'files(id,name,webViewLink,parents,appProperties,createdTime)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $files = $result->getFiles() ?? [];

        if (count($files) > 1) {
            throw new RuntimeException(
                'Terdeteksi lebih dari satu spreadsheet Error Log untuk root + bulan '
                . $monthKey . ' di folder bulanan. Rapikan duplikat sebelum melanjutkan.'
            );
        }

        return $files[0] ?? null;
    }

    private function findLegacyErrorLogByName(
        GoogleDrive $drive,
        string $monthFolderId
    ): ?DriveFile {
        $parent = $this->escapeDriveQueryValue($monthFolderId);

        $query = "'{$parent}' in parents "
            . "and mimeType = '" . self::GOOGLE_SHEET_MIME . "' "
            . 'and trashed = false';

        $result = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'pageSize' => 100,
            'orderBy' => 'createdTime',
            'fields' => 'files(id,name,webViewLink,parents,appProperties,createdTime)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $matches = [];
        foreach ($result->getFiles() ?? [] as $candidate) {
            $name = mb_strtoupper((string) $candidate->getName(), 'UTF-8');
            if (str_contains($name, 'ERROR') && str_contains($name, 'LOG')) {
                $matches[] = $candidate;
            }
        }

        if (count($matches) > 1) {
            throw new RuntimeException(
                'Folder bulan memiliki lebih dari satu spreadsheet yang terlihat seperti Error Log. '
                . 'Ubah nama/rapikan file agar SIMOLA dapat menentukan file yang benar.'
            );
        }

        return $matches[0] ?? null;
    }

    private function extractDriveId(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (preg_match('~/folders/([a-zA-Z0-9_-]+)~', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $value, $matches)) {
            return $matches[1];
        }

        return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1
            ? $value
            : '';
    }

    private function escapeDriveQueryValue(string $value): string
    {
        return str_replace(
            ["\\", "'"],
            ["\\\\", "\\'"],
            $value
        );
    }

    private function indonesianMonthName(int $month): string
    {
        return match ($month) {
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARET',
            4 => 'APRIL',
            5 => 'MEI',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AGUSTUS',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DESEMBER',
            default => throw new RuntimeException('Bulan tidak valid.'),
        };
    }
}
