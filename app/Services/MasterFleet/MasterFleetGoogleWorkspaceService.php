<?php

namespace App\Services\MasterFleet;

use App\Models\FleetGoogleAccount;
use App\Models\FleetGoogleEvidenceFolder;
use App\Models\FleetGoogleK302DailyFile;
use App\Models\FleetGoogleSyncLog;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetGroupingPeriod;
use App\Models\FleetVehicle;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2 as GoogleOauth2;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\AddSheetRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\DeleteSheetRequest;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\SheetProperties;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MasterFleetGoogleWorkspaceService
{
    /**
     * Warna merah folder nopol kendaraan P1.
     * Google Drive akan menggunakan warna terdekat
     * dari palet folder yang didukung.
     */
    private const P1_EVIDENCE_FOLDER_COLOR_RGB =
        '#d93025';

    /**
     * Membuat client untuk proses persetujuan OAuth.
     */
    public function authorizationClient(): GoogleClient
    {
        $clientId = trim(
            (string) config(
                'services.google_workspace.client_id'
            )
        );

        $clientSecret = trim(
            (string) config(
                'services.google_workspace.client_secret'
            )
        );

        $redirectUri = trim(
            (string) config(
                'services.google_workspace.redirect_uri'
            )
        );

        if (
            $clientId === ''
            || $clientSecret === ''
            || $redirectUri === ''
        ) {
            throw new RuntimeException(
                'OAuth Google Workspace belum dikonfigurasi. '
                . 'Isi GOOGLE_WORKSPACE_CLIENT_ID, '
                . 'GOOGLE_WORKSPACE_CLIENT_SECRET, dan '
                . 'GOOGLE_WORKSPACE_REDIRECT_URI pada file .env.'
            );
        }

        $client = new GoogleClient();

        $client->setApplicationName(
            'SIMOLA Master Fleet Workspace'
        );

        $client->setClientId(
            $clientId
        );

        $client->setClientSecret(
            $clientSecret
        );

        $client->setRedirectUri(
            $redirectUri
        );

        $client->setAccessType(
            'offline'
        );

        /*
         * Selalu tampilkan pemilih akun agar K3-02 dan Evidence dapat
         * dihubungkan tanpa logout dari akun Google lain.
         */
        $client->setPrompt(
            'select_account consent'
        );

        $client->setIncludeGrantedScopes(
            true
        );

        $client->setScopes([
            GoogleDrive::DRIVE,
            GoogleSheets::SPREADSHEETS,
            GoogleOauth2::USERINFO_EMAIL,
        ]);

        return $client;
    }

    public function authorizationUrl(
        string $state
    ): string {
        $client = $this->authorizationClient();

        $client->setState(
            $state
        );

        return $client->createAuthUrl();
    }

    /**
     * Menukar authorization code dan menyimpan token secara terenkripsi.
     */
    public function connect(
        User $user,
        string $authorizationCode,
        string $purpose
    ): FleetGoogleAccount {
        $purpose = $this->normalizePurpose(
            $purpose
        );

        $client = $this->authorizationClient();

        $token = $client->fetchAccessTokenWithAuthCode(
            $authorizationCode
        );

        if (isset($token['error'])) {
            throw new RuntimeException(
                'Google menolak proses OAuth: '
                . (
                    $token['error_description']
                    ?? $token['error']
                )
            );
        }

        $client->setAccessToken(
            $token
        );

        $oauth2 = new GoogleOauth2(
            $client
        );

        $googleUser =
            $oauth2->userinfo->get();

        $googleEmail = trim(
            (string) $googleUser->getEmail()
        );

        $existing = FleetGoogleAccount::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'purpose',
                $purpose
            )
            ->first();

        /*
         * Google dapat tidak mengirim refresh_token saat akun yang sama
         * memberi persetujuan ulang. Pertahankan token lama hanya bila
         * email Google tetap sama. Jangan pernah memakai refresh token
         * akun lama ketika slot koneksi diganti ke akun lain.
         */
        if (
            empty($token['refresh_token'])
            && $existing !== null
            && mb_strtolower(
                (string) $existing->google_email,
                'UTF-8'
            ) === mb_strtolower(
                $googleEmail,
                'UTF-8'
            )
        ) {
            $existingToken =
                $existing->token_payload
                ?? [];

            if (
                !empty(
                    $existingToken['refresh_token']
                    ?? null
                )
            ) {
                $token['refresh_token'] =
                    $existingToken['refresh_token'];
            }
        }

        if (empty($token['refresh_token'])) {
            throw new RuntimeException(
                'Google tidak mengirim refresh token untuk akun '
                . ($googleEmail !== '' ? $googleEmail : 'terpilih')
                . '. Cabut akses aplikasi SIMOLA dari akun Google '
                . 'tersebut, lalu hubungkan kembali dan setujui izin.'
            );
        }

        return FleetGoogleAccount::query()
            ->updateOrCreate(
                [
                    'user_id' =>
                        $user->id,

                    'purpose' =>
                        $purpose,
                ],
                [
                    'google_email' =>
                        $googleEmail,

                    'token_payload' =>
                        $token,

                    'scopes' =>
                        $this->normalizeScopes(
                            $token['scope']
                            ?? null
                        ),

                    'connected_at' =>
                        now(),

                    'last_refreshed_at' =>
                        null,
                ]
            );
    }

    public function disconnect(
        User $user,
        string $purpose
    ): void {
        $purpose = $this->normalizePurpose(
            $purpose
        );

        $account = FleetGoogleAccount::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'purpose',
                $purpose
            )
            ->first();

        if ($account === null) {
            return;
        }

        try {
            $client = $this->authorizationClient();

            $client->setAccessToken(
                $account->token_payload
            );

            $client->revokeToken();
        } catch (Throwable) {
            /*
             * Tetap hapus token lokal walaupun revoke ke Google gagal.
             */
        }

        $account->delete();
    }

    /**
     * Menyinkronkan PC Set Utama ke tab SIMOLA_PC_SET.
     */
    public function syncPcSet(
        User $user
    ): FleetGoogleSyncLog {
        $period = $this->latestPublishedPeriod();

        $assignments =
            $this->publishedAssignments(
                $period
            );

        $log = FleetGoogleSyncLog::query()
            ->create([
                'sync_type' =>
                    'pc_set_spreadsheet',

                'status' =>
                    'running',

                'grouping_period_id' =>
                    $period->id,

                'total_items' =>
                    $assignments->count(),

                'started_at' =>
                    now(),

                'created_by' =>
                    $user->id,
            ]);

        try {
            $client = $this->authorizedClient(
                $user,
                FleetGoogleAccount::PURPOSE_K302
            );

            $sheets = new GoogleSheets(
                $client
            );

            $spreadsheetId =
                $this->requiredConfig(
                    'source_spreadsheet_id',
                    'ID spreadsheet sumber'
                );

            $sheetName =
                $this->requiredConfig(
                    'source_sheet_name',
                    'Nama tab spreadsheet sumber'
                );

            $this->ensureSheetExists(
                $sheets,
                $spreadsheetId,
                $sheetName
            );

            $escapedSheetName =
                $this->escapeSheetName(
                    $sheetName
                );

            $sheets
                ->spreadsheets_values
                ->clear(
                    $spreadsheetId,
                    "'{$escapedSheetName}'!A:H",
                    new ClearValuesRequest()
                );

            $rows = [[
                'NO',
                'PC',
                'NOPOL',
                'TLPG',
                'TIPE',
                'OPERATOR / SPBE',
                'PERIODE',
                'TERAKHIR DIPERBARUI',
            ]];

            $updatedAt =
                now()->format(
                    'd-m-Y H:i:s'
                );

            foreach (
                $assignments->values()
                as $index => $assignment
            ) {
                $isP1 =
                    $assignment->operational_type
                    === FleetVehicle::TYPE_P1;

                $rows[] = [
                    $index + 1,
                    'PC ' . $assignment->pc_final,
                    $assignment->plate_number_snapshot,
                    $assignment->terminal_name_snapshot,
                    $assignment->operational_type,
                    $isP1
                        ? $assignment->operator_name_snapshot
                        : $assignment->company_name_snapshot,
                    $period->name,
                    $updatedAt,
                ];
            }

            /*
             * Google Sheets hanya menerima values sebagai array dua dimensi
             * berindeks numerik yang berisi nilai scalar. Snapshot lama dapat
             * mempunyai NULL pada TLPG, operator, atau perusahaan; NULL dan
             * array dengan key yang tidak berurutan dapat menghasilkan payload
             * JSON yang ditolak Google sebagai "Unknown name 0".
             */
            $rows = $this->normalizeSheetRows(
                $rows
            );

            $range =
                "'{$escapedSheetName}'!A1:H"
                . count($rows);

            $valueRange = new ValueRange();

            $valueRange->setRange(
                $range
            );

            $valueRange->setMajorDimension(
                'ROWS'
            );

            $valueRange->setValues(
                $rows
            );

            $response =
                $sheets
                    ->spreadsheets_values
                    ->update(
                        $spreadsheetId,
                        $range,
                        $valueRange,
                        [
                            'valueInputOption' =>
                                'RAW',
                        ]
                    );

            $log->update([
                'status' =>
                    'success',

                'updated_items' =>
                    $assignments->count(),

                'message' =>
                    'PC Set Utama berhasil disinkronkan ke Google Spreadsheet.',

                'metadata' => [
                    'spreadsheet_id' =>
                        $spreadsheetId,

                    'sheet_name' =>
                        $sheetName,

                    'account_purpose' =>
                        FleetGoogleAccount::PURPOSE_K302,

                    'google_email' =>
                        $this->accountFor(
                            $user,
                            FleetGoogleAccount::PURPOSE_K302
                        )->google_email,

                    'updated_rows' =>
                        $response->getUpdatedRows(),

                    'updated_cells' =>
                        $response->getUpdatedCells(),
                ],

                'finished_at' =>
                    now(),
            ]);

            return $log->refresh();
        } catch (Throwable $exception) {
            $message = $this->compactExceptionMessage(
                $exception,
                'Sinkronisasi PC Set ke Google Spreadsheet gagal.'
            );

            $log->update([
                'status' =>
                    'failed',

                'failed_items' =>
                    $assignments->count(),

                'message' =>
                    $message,

                'metadata' => [
                    'exception_class' =>
                        $exception::class,

                    'account_purpose' =>
                        FleetGoogleAccount::PURPOSE_K302,
                ],

                'finished_at' =>
                    now(),
            ]);

            throw new RuntimeException(
                $message,
                0,
                $exception
            );
        }
    }

    /**
     * Membuat spreadsheet K3-02 harian:
     * BULAN / TANGGAL / salinan template K3-02.2.
     */
    public function generateK302DailySpreadsheet(
        User $user,
        Carbon $workspaceDate,
        bool $syncMaster = true
    ): FleetGoogleSyncLog {
        set_time_limit(600);

        /*
         * Pada proses tunggal, pastikan spreadsheet master selalu berisi
         * PC Set terbaru. Proses batch menyinkronkan master hanya sekali
         * sebelum membuat seluruh tanggal.
         */
        if ($syncMaster) {
            $this->syncPcSet(
                $user
            );
        }

        $period = $this->latestPublishedPeriod();

        $vehicleCount =
            $this->publishedAssignments(
                $period
            )->count();

        $log = FleetGoogleSyncLog::query()
            ->create([
                'sync_type' =>
                    'k302_daily_spreadsheet',

                'status' =>
                    'running',

                'grouping_period_id' =>
                    $period->id,

                'target_date' =>
                    $workspaceDate->toDateString(),

                'total_items' =>
                    1,

                'started_at' =>
                    now(),

                'created_by' =>
                    $user->id,
            ]);

        try {
            $client = $this->authorizedClient(
                $user,
                FleetGoogleAccount::PURPOSE_K302
            );

            $drive = new GoogleDrive(
                $client
            );

            $sheets = new GoogleSheets(
                $client
            );

            $rootFolderId =
                $this->requiredConfig(
                    'k302_root_folder_id',
                    'ID folder root K3-02'
                );

            $templateSpreadsheetId =
                $this->requiredConfig(
                    'k302_template_spreadsheet_id',
                    'ID template spreadsheet K3-02'
                );

            $rootFolder = $drive->files->get(
                $rootFolderId,
                [
                    'fields' =>
                        'id,name,mimeType',

                    'supportsAllDrives' =>
                        true,
                ]
            );

            if (
                $rootFolder->getMimeType()
                !== 'application/vnd.google-apps.folder'
            ) {
                throw new RuntimeException(
                    'K302_ROOT_FOLDER_ID bukan folder Google Drive.'
                );
            }

            $templateFile = $drive->files->get(
                $templateSpreadsheetId,
                [
                    'fields' =>
                        'id,name,mimeType',

                    'supportsAllDrives' =>
                        true,
                ]
            );

            if (
                $templateFile->getMimeType()
                !== 'application/vnd.google-apps.spreadsheet'
            ) {
                throw new RuntimeException(
                    'K302_TEMPLATE_SPREADSHEET_ID bukan Google Spreadsheet.'
                );
            }

            $monthName =
                $this->indonesianMonthName(
                    (int) $workspaceDate->month
                );

            [$monthFolderId] =
                $this->findOrCreateFolder(
                    $drive,
                    $rootFolderId,
                    $monthName,
                    [
                        'simola_type' =>
                            'k302_month',

                        'simola_month' =>
                            $workspaceDate->format(
                                'Y-m'
                            ),
                    ]
                );

            $dateFolderName =
                $workspaceDate->format(
                    'd-m-Y'
                );

            [$dateFolderId] =
                $this->findOrCreateFolder(
                    $drive,
                    $monthFolderId,
                    $dateFolderName,
                    [
                        'simola_type' =>
                            'k302_date',

                        'simola_date' =>
                            $workspaceDate
                                ->toDateString(),
                    ]
                );

            $filePrefix = trim(
                (string) config(
                    'services.google_workspace.k302_daily_file_prefix',
                    'K3-02.2 HARIAN OPERATOR -'
                )
            );

            $fileName = trim(
                $filePrefix
                . ' '
                . $dateFolderName
            );

            $dailyFile =
                FleetGoogleK302DailyFile::query()
                    ->whereDate(
                        'workspace_date',
                        $workspaceDate->toDateString()
                    )
                    ->first();

            $spreadsheetId =
                $dailyFile?->spreadsheet_id;

            $wasCreated = false;

            if (
                !is_string($spreadsheetId)
                || trim($spreadsheetId) === ''
            ) {
                $existingDriveFile =
                    $this->findSpreadsheetInFolder(
                        $drive,
                        $dateFolderId,
                        $fileName
                    );

                $spreadsheetId =
                    $existingDriveFile?->getId();
            }

            if (
                !is_string($spreadsheetId)
                || trim($spreadsheetId) === ''
            ) {
                $copyMetadata = new DriveFile([
                    'name' =>
                        $fileName,

                    'parents' => [
                        $dateFolderId,
                    ],

                    'appProperties' => [
                        'simola_type' =>
                            'k302_daily_spreadsheet',

                        'simola_date' =>
                            $workspaceDate
                                ->toDateString(),
                    ],
                ]);

                $copy = $drive->files->copy(
                    $templateSpreadsheetId,
                    $copyMetadata,
                    [
                        'fields' =>
                            'id,name,webViewLink,parents',

                        'supportsAllDrives' =>
                            true,
                    ]
                );

                $spreadsheetId =
                    (string) $copy->getId();

                $wasCreated = true;
            }

            $spreadsheetUrl =
                'https://docs.google.com/spreadsheets/d/'
                . $spreadsheetId
                . '/edit';

            $this->configureK302DailySpreadsheet(
                $sheets,
                $spreadsheetId,
                $workspaceDate
            );

            $dailyFile =
                FleetGoogleK302DailyFile::query()
                    ->updateOrCreate(
                        [
                            'workspace_date' =>
                                $workspaceDate
                                    ->toDateString(),
                        ],
                        [
                            'grouping_period_id' =>
                                $period->id,

                            'month_folder_id' =>
                                $monthFolderId,

                            'date_folder_id' =>
                                $dateFolderId,

                            'template_spreadsheet_id' =>
                                $templateSpreadsheetId,

                            'spreadsheet_id' =>
                                $spreadsheetId,

                            'spreadsheet_name' =>
                                $fileName,

                            'spreadsheet_url' =>
                                $spreadsheetUrl,

                            'status' =>
                                'active',

                            'last_synced_at' =>
                                now(),

                            'metadata' => [
                                'vehicle_count' =>
                                    $vehicleCount,

                                'google_email' =>
                                    $this->accountFor(
                                        $user,
                                        FleetGoogleAccount::PURPOSE_K302
                                    )->google_email,
                            ],

                            'created_by' =>
                                $user->id,
                        ]
                    );

            $message = $wasCreated
                ? 'Spreadsheet K3-02 harian berhasil dibuat.'
                : 'Spreadsheet K3-02 tanggal tersebut sudah ada dan telah disinkronkan ulang.';

            $log->update([
                'status' =>
                    'success',

                'created_items' =>
                    $wasCreated
                        ? 1
                        : 0,

                'updated_items' =>
                    $wasCreated
                        ? 0
                        : 1,

                'message' =>
                    $message,

                'metadata' => [
                    'workspace_date' =>
                        $workspaceDate
                            ->toDateString(),

                    'vehicle_count' =>
                        $vehicleCount,

                    'month_folder_id' =>
                        $monthFolderId,

                    'date_folder_id' =>
                        $dateFolderId,

                    'date_folder_url' =>
                        'https://drive.google.com/drive/folders/'
                        . $dateFolderId,

                    'spreadsheet_id' =>
                        $spreadsheetId,

                    'spreadsheet_name' =>
                        $fileName,

                    'spreadsheet_url' =>
                        $spreadsheetUrl,

                    'daily_file_id' =>
                        $dailyFile->id,

                    'account_purpose' =>
                        FleetGoogleAccount::PURPOSE_K302,
                ],

                'finished_at' =>
                    now(),
            ]);

            return $log->refresh();
        } catch (Throwable $exception) {
            $message = $this->compactExceptionMessage(
                $exception,
                'Pembuatan spreadsheet K3-02 harian gagal.'
            );

            $log->update([
                'status' =>
                    'failed',

                'failed_items' =>
                    1,

                'message' =>
                    $message,

                'metadata' => [
                    'exception_class' =>
                        $exception::class,

                    'account_purpose' =>
                        FleetGoogleAccount::PURPOSE_K302,
                ],

                'finished_at' =>
                    now(),
            ]);

            throw new RuntimeException(
                $message,
                0,
                $exception
            );
        }
    }

    /**
     * Membuat banyak spreadsheet K3-02 harian dalam satu proses.
     * Setiap tanggal tetap memiliki folder dan file tersendiri.
     *
     * @return array{
     *     total: int,
     *     created: int,
     *     updated: int,
     *     failed: int,
     *     start_date: string,
     *     end_date: string,
     *     first_url: string|null,
     *     last_url: string|null,
     *     errors: array<int, string>,
     *     message: string
     * }
     */
    public function generateK302SpreadsheetBatch(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        /*
         * Proses Evidence dijalankan melalui queue worker CLI.
         * Jangan batasi dengan timeout request HTTP 30 menit.
         */
        set_time_limit(0);

        $startDate = $startDate
            ->copy()
            ->startOfDay();

        $endDate = $endDate
            ->copy()
            ->startOfDay();

        if ($endDate->lt($startDate)) {
            throw new RuntimeException(
                'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.'
            );
        }

        $totalDays = $startDate->diffInDays(
            $endDate
        ) + 1;

        if ($totalDays > 31) {
            throw new RuntimeException(
                'Maksimal pembuatan batch adalah 31 tanggal dalam satu proses.'
            );
        }

        /*
         * Sinkronkan sumber PC Set hanya sekali. File harian berikutnya
         * menggunakan snapshot master yang sama sehingga proses lebih cepat
         * dan tidak mengirim 7/31 sinkronisasi yang identik.
         */
        $this->syncPcSet(
            $user
        );

        $created = 0;
        $updated = 0;
        $failed = 0;
        $firstUrl = null;
        $lastUrl = null;
        $errors = [];

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            try {
                $log = $this->generateK302DailySpreadsheet(
                    $user,
                    $currentDate->copy(),
                    false
                );

                $created += (int) ($log->created_items ?? 0);
                $updated += (int) ($log->updated_items ?? 0);

                $spreadsheetUrl = is_array($log->metadata)
                    ? ($log->metadata['spreadsheet_url'] ?? null)
                    : null;

                if (
                    is_string($spreadsheetUrl)
                    && $spreadsheetUrl !== ''
                ) {
                    $firstUrl ??= $spreadsheetUrl;
                    $lastUrl = $spreadsheetUrl;
                }
            } catch (Throwable $exception) {
                $failed++;

                $errors[] = $currentDate->format('d-m-Y')
                    . ': '
                    . $this->compactExceptionMessage(
                        $exception,
                        'Gagal membuat K3-02.'
                    );
            }

            $currentDate->addDay();
        }

        $successful = $created + $updated;

        $message = sprintf(
            'Batch K3-02 selesai untuk %d tanggal: %d berhasil (%d baru, %d disinkronkan ulang), %d gagal.',
            $totalDays,
            $successful,
            $created,
            $updated,
            $failed
        );

        return [
            'total' =>
                $totalDays,

            'created' =>
                $created,

            'updated' =>
                $updated,

            'failed' =>
                $failed,

            'start_date' =>
                $startDate->toDateString(),

            'end_date' =>
                $endDate->toDateString(),

            'first_url' =>
                $firstUrl,

            'last_url' =>
                $lastUrl,

            'errors' =>
                $errors,

            'message' =>
                $message,
        ];
    }

    /**
     * Membuat folder:
     * BULAN / TANGGAL / PC / NOPOL / 4 kategori.
     */
    public function generateEvidenceFolders(
        User $user,
        Carbon $workspaceDate
    ): FleetGoogleSyncLog {
        /*
         * Evidence diproses oleh background queue worker CLI.
         * Tidak memakai batas waktu request browser.
         */
        set_time_limit(0);

        $period = $this->latestPublishedPeriod();

        $assignments =
            $this->publishedAssignments(
                $period
            );

        $log = FleetGoogleSyncLog::query()
            ->create([
                'sync_type' =>
                    'evidence_folders',

                'status' =>
                    'running',

                'grouping_period_id' =>
                    $period->id,

                'target_date' =>
                    $workspaceDate->toDateString(),

                'total_items' =>
                    $assignments->count(),

                'started_at' =>
                    now(),

                'created_by' =>
                    $user->id,
            ]);

        $created = 0;
        $skipped = 0;
        $failed = 0;

        try {
            $client = $this->authorizedClient(
                $user,
                FleetGoogleAccount::PURPOSE_EVIDENCE
            );

            $drive = new GoogleDrive(
                $client
            );

            $rootFolderId =
                $this->requiredConfig(
                    'evidence_root_folder_id',
                    'ID folder root Evidence'
                );

            /*
             * Verifikasi folder root dapat diakses oleh akun OAuth.
             */
            $drive->files->get(
                $rootFolderId,
                [
                    'fields' =>
                        'id,name,mimeType',

                    'supportsAllDrives' =>
                        true,
                ]
            );

            $monthName =
                $this->indonesianMonthName(
                    (int) $workspaceDate->month
                );

            [$monthFolderId] =
                $this->findOrCreateFolder(
                    $drive,
                    $rootFolderId,
                    $monthName,
                    [
                        'simola_type' =>
                            'evidence_month',

                        'simola_month' =>
                            $workspaceDate->format(
                                'Y-m'
                            ),
                    ]
                );

            $dateFolderName =
                $workspaceDate->format(
                    'd-m-Y'
                );

            [$dateFolderId] =
                $this->findOrCreateFolder(
                    $drive,
                    $monthFolderId,
                    $dateFolderName,
                    [
                        'simola_type' =>
                            'evidence_date',

                        'simola_date' =>
                            $workspaceDate
                                ->toDateString(),
                    ]
                );

            $pcFolderCache = [];

            foreach ($assignments as $assignment) {
                try {
                    $pcNumber =
                        (int) $assignment->pc_final;

                    $isP1 =
                        $assignment->operational_type
                        === FleetVehicle::TYPE_P1;

                    $normalizedPlate =
                        FleetVehicle::normalizePlateNumber(
                            (string) $assignment
                                ->plate_number_snapshot
                        );

                    $existing =
                        FleetGoogleEvidenceFolder::query()
                            ->where(
                                'grouping_period_id',
                                $period->id
                            )
                            ->whereDate(
                                'workspace_date',
                                $workspaceDate
                                    ->toDateString()
                            )
                            ->where(
                                'pc_number',
                                $pcNumber
                            )
                            ->where(
                                'normalized_plate_number_snapshot',
                                $normalizedPlate
                            )
                            ->first();

                    if ($existing !== null) {
                        if (
                            $isP1
                            && filled(
                                $existing->vehicle_folder_id
                            )
                        ) {
                            $this->setFolderColor(
                                $drive,
                                (string) $existing
                                    ->vehicle_folder_id,
                                self::P1_EVIDENCE_FOLDER_COLOR_RGB
                            );
                        }

                        $skipped++;

                        continue;
                    }

                    if (
                        !isset(
                            $pcFolderCache[$pcNumber]
                        )
                    ) {
                        [$pcFolderId] =
                            $this->findOrCreateFolder(
                                $drive,
                                $dateFolderId,
                                'PC ' . $pcNumber,
                                [
                                    'simola_type' =>
                                        'evidence_pc',

                                    'simola_date' =>
                                        $workspaceDate
                                            ->toDateString(),

                                    'simola_pc' =>
                                        (string) $pcNumber,
                                ]
                            );

                        $pcFolderCache[$pcNumber] =
                            $pcFolderId;
                    }

                    $pcFolderId =
                        $pcFolderCache[$pcNumber];

                    [$vehicleFolderId] =
                        $this->findOrCreateFolder(
                            $drive,
                            $pcFolderId,
                            $assignment
                                ->plate_number_snapshot,
                            [
                                'simola_type' =>
                                    'evidence_vehicle',

                                'simola_date' =>
                                    $workspaceDate
                                        ->toDateString(),

                                'simola_pc' =>
                                    (string) $pcNumber,

                                'simola_plate' =>
                                    $normalizedPlate,
                            ]
                        );

                    if ($isP1) {
                        $this->setFolderColor(
                            $drive,
                            $vehicleFolderId,
                            self::P1_EVIDENCE_FOLDER_COLOR_RGB
                        );
                    }

                    $categoryIds = [];

                    foreach (
                        [
                            'PELANGGARAN',
                            'ERRORLOG',
                            'ACCIDENT',
                            'INSIDEN',
                        ]
                        as $category
                    ) {
                        [$categoryFolderId] =
                            $this->findOrCreateFolder(
                                $drive,
                                $vehicleFolderId,
                                $category,
                                [
                                    'simola_type' =>
                                        'evidence_category',

                                    'simola_category' =>
                                        $category,

                                    'simola_date' =>
                                        $workspaceDate
                                            ->toDateString(),

                                    'simola_plate' =>
                                        $normalizedPlate,
                                ]
                            );

                        $categoryIds[$category] =
                            $categoryFolderId;
                    }

                    FleetGoogleEvidenceFolder::query()
                        ->create([
                            'grouping_period_id' =>
                                $period->id,

                            'workspace_date' =>
                                $workspaceDate
                                    ->toDateString(),

                            'pc_number' =>
                                $pcNumber,

                            'vehicle_id' =>
                                $assignment->vehicle_id,

                            'plate_number_snapshot' =>
                                $assignment
                                    ->plate_number_snapshot,

                            'normalized_plate_number_snapshot' =>
                                $normalizedPlate,

                            'month_folder_id' =>
                                $monthFolderId,

                            'date_folder_id' =>
                                $dateFolderId,

                            'pc_folder_id' =>
                                $pcFolderId,

                            'vehicle_folder_id' =>
                                $vehicleFolderId,

                            'pelanggaran_folder_id' =>
                                $categoryIds[
                                    'PELANGGARAN'
                                ],

                            'errorlog_folder_id' =>
                                $categoryIds[
                                    'ERRORLOG'
                                ],

                            'accident_folder_id' =>
                                $categoryIds[
                                    'ACCIDENT'
                                ],

                            'insiden_folder_id' =>
                                $categoryIds[
                                    'INSIDEN'
                                ],
                        ]);

                    $created++;
                } catch (Throwable $vehicleException) {
                    $failed++;

                    report(
                        $vehicleException
                    );
                }

                $processedCount =
                    $created
                    + $skipped
                    + $failed;

                if (
                    $processedCount % 5 === 0
                    || $processedCount
                        === $assignments->count()
                ) {
                    $progressPercent =
                        $assignments->count() > 0
                            ? min(
                                100,
                                (int) round(
                                    $processedCount
                                    / $assignments->count()
                                    * 100
                                )
                            )
                            : 0;

                    $log->update([
                        'created_items' =>
                            $created,

                        'skipped_items' =>
                            $skipped,

                        'failed_items' =>
                            $failed,

                        'message' =>
                            'Memproses kendaraan '
                            . $processedCount
                            . ' dari '
                            . $assignments->count()
                            . ' ('
                            . $progressPercent
                            . '%).',
                    ]);
                }
            }

            $finalStatus =
                $failed > 0
                    ? 'partial'
                    : 'success';

            $log->update([
                'status' =>
                    $finalStatus,

                'created_items' =>
                    $created,

                'skipped_items' =>
                    $skipped,

                'failed_items' =>
                    $failed,

                'message' =>
                    $failed > 0
                        ? 'Folder Evidence selesai dibuat dengan beberapa kegagalan.'
                        : 'Folder Evidence berhasil dibuat.',

                'metadata' => [
                    'root_folder_id' =>
                        $rootFolderId,

                    'account_purpose' =>
                        FleetGoogleAccount::PURPOSE_EVIDENCE,

                    'google_email' =>
                        $this->accountFor(
                            $user,
                            FleetGoogleAccount::PURPOSE_EVIDENCE
                        )->google_email,

                    'month_folder_id' =>
                        $monthFolderId,

                    'date_folder_id' =>
                        $dateFolderId,

                    'date_folder_url' =>
                        'https://drive.google.com/drive/folders/'
                        . $dateFolderId,
                ],

                'finished_at' =>
                    now(),
            ]);

            return $log->refresh();
        } catch (Throwable $exception) {
            $log->update([
                'status' =>
                    'failed',

                'created_items' =>
                    $created,

                'skipped_items' =>
                    $skipped,

                'failed_items' =>
                    max(
                        $failed,
                        $assignments->count()
                        - $created
                        - $skipped
                    ),

                'message' =>
                    $exception->getMessage(),

                'finished_at' =>
                    now(),
            ]);

            throw $exception;
        }
    }

    public function latestPublishedPeriod(): FleetGroupingPeriod
    {
        $period = FleetGroupingPeriod::query()
            ->where(
                'status',
                'published'
            )
            ->orderByDesc(
                'published_at'
            )
            ->orderByDesc(
                'id'
            )
            ->first();

        if ($period === null) {
            throw new RuntimeException(
                'Belum ada PC Set Utama berstatus published.'
            );
        }

        return $period;
    }

    public function publishedAssignments(
        FleetGroupingPeriod $period
    ): Collection {
        return FleetGroupingAssignment::query()
            ->where(
                'grouping_period_id',
                $period->id
            )
            ->whereNotNull(
                'pc_final'
            )
            ->orderBy(
                'pc_final'
            )
            ->orderBy(
                'plate_number_snapshot'
            )
            ->get();
    }

    /**
     * Membuat client OAuth siap pakai dan memperbarui token jika kedaluwarsa.
     */
    private function authorizedClient(
        User $user,
        string $purpose
    ): GoogleClient {
        $account = $this->accountFor(
            $user,
            $purpose
        );

        $client = $this->authorizationClient();

        $token =
            $account->token_payload
            ?? [];

        $client->setAccessToken(
            $token
        );

        if ($client->isAccessTokenExpired()) {
            $refreshToken =
                $token['refresh_token']
                ?? null;

            if (
                !is_string($refreshToken)
                || trim($refreshToken) === ''
            ) {
                throw new RuntimeException(
                    'Refresh token Google untuk '
                    . $account->purposeLabel()
                    . ' tidak tersedia. Putuskan lalu hubungkan '
                    . 'kembali akun Google tersebut.'
                );
            }

            $newToken =
                $client
                    ->fetchAccessTokenWithRefreshToken(
                        $refreshToken
                    );

            if (isset($newToken['error'])) {
                throw new RuntimeException(
                    'Token Google '
                    . $account->purposeLabel()
                    . ' gagal diperbarui: '
                    . (
                        $newToken['error_description']
                        ?? $newToken['error']
                    )
                );
            }

            $newToken['refresh_token'] =
                $refreshToken;

            $account->update([
                'token_payload' =>
                    $newToken,

                'last_refreshed_at' =>
                    now(),
            ]);

            $client->setAccessToken(
                $newToken
            );
        }

        return $client;
    }

    private function accountFor(
        User $user,
        string $purpose
    ): FleetGoogleAccount {
        $purpose = $this->normalizePurpose(
            $purpose
        );

        $account = FleetGoogleAccount::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'purpose',
                $purpose
            )
            ->first();

        if ($account === null) {
            $label = match ($purpose) {
                FleetGoogleAccount::PURPOSE_K302 => 'K3-02',
                FleetGoogleAccount::PURPOSE_EVIDENCE => 'Evidence',
                default => strtoupper($purpose),
            };

            throw new RuntimeException(
                'Akun Google '
                . $label
                . ' belum dihubungkan ke SIMOLA.'
            );
        }

        return $account;
    }

    public function normalizePurpose(
        string $purpose
    ): string {
        $purpose = mb_strtolower(
            trim($purpose),
            'UTF-8'
        );

        if (
            !in_array(
                $purpose,
                FleetGoogleAccount::purposes(),
                true
            )
        ) {
            throw new RuntimeException(
                'Tujuan koneksi Google tidak valid.'
            );
        }

        return $purpose;
    }

    private function ensureSheetExists(
        GoogleSheets $sheets,
        string $spreadsheetId,
        string $sheetName
    ): void {
        $spreadsheet =
            $sheets
                ->spreadsheets
                ->get(
                    $spreadsheetId,
                    [
                        'fields' =>
                            'sheets.properties',
                    ]
                );

        foreach (
            $spreadsheet->getSheets()
            ?? []
            as $sheet
        ) {
            if (
                $sheet
                    ->getProperties()
                    ?->getTitle()
                === $sheetName
            ) {
                return;
            }
        }

        $addSheetRequest =
            new AddSheetRequest([
                'properties' =>
                    new SheetProperties([
                        'title' =>
                            $sheetName,
                    ]),
            ]);

        $request =
            new SheetsRequest([
                'addSheet' =>
                    $addSheetRequest,
            ]);

        $batchRequest =
            new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    $request,
                ],
            ]);

        $sheets
            ->spreadsheets
            ->batchUpdate(
                $spreadsheetId,
                $batchRequest
            );
    }

    /**
     * Memberi warna pada folder Google Drive.
     */
    private function setFolderColor(
        GoogleDrive $drive,
        string $folderId,
        string $colorRgb
    ): void {
        $folder = new DriveFile([
            'folderColorRgb' =>
                $colorRgb,
        ]);

        $drive
            ->files
            ->update(
                $folderId,
                $folder,
                [
                    'fields' =>
                        'id,folderColorRgb',

                    'supportsAllDrives' =>
                        true,
                ]
            );
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function findOrCreateFolder(
        GoogleDrive $drive,
        string $parentFolderId,
        string $folderName,
        array $appProperties = []
    ): array {
        $safeName =
            str_replace(
                "'",
                "\\'",
                $folderName
            );

        $query =
            "'{$parentFolderId}' in parents "
            . "and name = '{$safeName}' "
            . "and mimeType = 'application/vnd.google-apps.folder' "
            . 'and trashed = false';

        $result =
            $drive
                ->files
                ->listFiles([
                    'q' =>
                        $query,

                    'spaces' =>
                        'drive',

                    'pageSize' =>
                        10,

                    'fields' =>
                        'files(id,name,parents)',

                    'supportsAllDrives' =>
                        true,

                    'includeItemsFromAllDrives' =>
                        true,
                ]);

        $files =
            $result->getFiles()
            ?? [];

        if ($files !== []) {
            return [
                $files[0]->getId(),
                false,
            ];
        }

        $folder = new DriveFile([
            'name' =>
                $folderName,

            'mimeType' =>
                'application/vnd.google-apps.folder',

            'parents' => [
                $parentFolderId,
            ],

            'appProperties' =>
                $appProperties,
        ]);

        $created =
            $drive
                ->files
                ->create(
                    $folder,
                    [
                        'fields' =>
                            'id,name',

                        'supportsAllDrives' =>
                            true,
                    ]
                );

        return [
            (string) $created->getId(),
            true,
        ];
    }

    private function findSpreadsheetInFolder(
        GoogleDrive $drive,
        string $parentFolderId,
        string $fileName
    ): ?DriveFile {
        $safeName = str_replace(
            "'",
            "\\'",
            $fileName
        );

        $query =
            "'{$parentFolderId}' in parents "
            . "and name = '{$safeName}' "
            . "and mimeType = 'application/vnd.google-apps.spreadsheet' "
            . 'and trashed = false';

        $result = $drive->files->listFiles([
            'q' =>
                $query,

            'spaces' =>
                'drive',

            'pageSize' =>
                10,

            'fields' =>
                'files(id,name,webViewLink,parents)',

            'supportsAllDrives' =>
                true,

            'includeItemsFromAllDrives' =>
                true,
        ]);

        $files = $result->getFiles()
            ?? [];

        return $files[0]
            ?? null;
    }

    private function configureK302DailySpreadsheet(
        GoogleSheets $sheets,
        string $spreadsheetId,
        Carbon $workspaceDate
    ): void {
        $sheetName = $this->requiredConfig(
            'k302_sheet_name',
            'Nama sheet template K3-02'
        );

        $sourceSpreadsheetId =
            $this->requiredConfig(
                'source_spreadsheet_id',
                'ID spreadsheet sumber'
            );

        $sourceSheetName =
            $this->requiredConfig(
                'source_sheet_name',
                'Nama tab spreadsheet sumber'
            );

        /*
         * Hasil harian hanya boleh menyisakan sheet laporan K3-02.2.
         * Sheet bantuan atau sheet master yang masih ada pada template
         * dihapus otomatis setelah file disalin.
         */
        $this->keepOnlyK302ReportSheet(
            $sheets,
            $spreadsheetId,
            $sheetName
        );

        /*
         * Bersihkan area spill lebih dahulu. Ini mencegah formula ARRAY/
         * IMPORTRANGE gagal dengan #REF! karena sel di bawahnya masih
         * berisi data lama dari template.
         */
        $this->clearK302DynamicRanges(
            $sheets,
            $spreadsheetId,
            $sheetName
        );

        $cells = [
            (string) config(
                'services.google_workspace.k302_nopol_start_cell',
                'C25'
            ) =>
                $this->buildImportRangeFormula(
                    $sourceSpreadsheetId,
                    $sourceSheetName,
                    3
                ),

            (string) config(
                'services.google_workspace.k302_tlpg_start_cell',
                'F25'
            ) =>
                $this->buildImportRangeFormula(
                    $sourceSpreadsheetId,
                    $sourceSheetName,
                    4
                ),

            (string) config(
                'services.google_workspace.k302_pc_start_cell',
                'AE25'
            ) =>
                $this->buildImportRangeFormula(
                    $sourceSpreadsheetId,
                    $sourceSheetName,
                    2
                ),

            (string) config(
                'services.google_workspace.k302_report_date_cell',
                'U6'
            ) =>
                $workspaceDate
                    ->copy()
                    ->locale('en')
                    ->isoFormat(
                        'dddd, DD MMMM YYYY'
                    ),

            (string) config(
                'services.google_workspace.k302_document_date_cell',
                'E6'
            ) =>
                $workspaceDate->format(
                    'j/n'
                ),

            (string) config(
                'services.google_workspace.k302_document_suffix_cell',
                'F6'
            ) =>
                '/K3-02.2//'
                . $workspaceDate->format(
                    'Y'
                ),
        ];

        /*
         * Versi generator sebelumnya pernah menulis tanggal ke P6.
         * Ketika target baru adalah U6, kosongkan P6 agar tidak ada
         * dua tanggal pada laporan hasil sinkronisasi ulang.
         */
        $reportDateCell = trim(
            (string) config(
                'services.google_workspace.k302_report_date_cell',
                'U6'
            )
        );

        if (
            strtoupper($reportDateCell) !== 'P6'
        ) {
            $legacyRange = "'"
                . $this->escapeSheetName(
                    $sheetName
                )
                . "'!P6";

            $sheets
                ->spreadsheets_values
                ->clear(
                    $spreadsheetId,
                    $legacyRange,
                    new ClearValuesRequest()
                );
        }

        foreach ($cells as $cell => $value) {
            $cell = trim(
                (string) $cell
            );

            if ($cell === '') {
                continue;
            }

            $range = "'"
                . $this->escapeSheetName(
                    $sheetName
                )
                . "'!"
                . $cell;

            $valueRange = new ValueRange();

            $valueRange->setRange(
                $range
            );

            $valueRange->setMajorDimension(
                'ROWS'
            );

            $valueRange->setValues([
                [
                    $value,
                ],
            ]);

            $sheets
                ->spreadsheets_values
                ->update(
                    $spreadsheetId,
                    $range,
                    $valueRange,
                    [
                        'valueInputOption' =>
                            'USER_ENTERED',
                    ]
                );
        }
    }

    /**
     * Hapus seluruh sheet selain sheet laporan utama.
     */
    private function keepOnlyK302ReportSheet(
        GoogleSheets $sheets,
        string $spreadsheetId,
        string $reportSheetName
    ): void {
        $spreadsheet = $sheets
            ->spreadsheets
            ->get(
                $spreadsheetId,
                [
                    'fields' =>
                        'sheets.properties(sheetId,title)',
                ]
            );

        $requests = [];
        $reportSheetFound = false;

        foreach (
            $spreadsheet->getSheets()
            ?? []
            as $sheet
        ) {
            $properties =
                $sheet->getProperties();

            $title = trim(
                (string) $properties?->getTitle()
            );

            $sheetId =
                $properties?->getSheetId();

            if ($title === $reportSheetName) {
                $reportSheetFound = true;
                continue;
            }

            if ($sheetId === null) {
                continue;
            }

            $requests[] = new SheetsRequest([
                'deleteSheet' =>
                    new DeleteSheetRequest([
                        'sheetId' =>
                            $sheetId,
                    ]),
            ]);
        }

        if (!$reportSheetFound) {
            throw new RuntimeException(
                'Sheet laporan K3-02 tidak ditemukan pada template: '
                . $reportSheetName
            );
        }

        if ($requests === []) {
            return;
        }

        $sheets
            ->spreadsheets
            ->batchUpdate(
                $spreadsheetId,
                new BatchUpdateSpreadsheetRequest([
                    'requests' =>
                        $requests,
                ])
            );
    }

    /**
     * Bersihkan kolom dinamis sebelum formula spill dipasang.
     */
    private function clearK302DynamicRanges(
        GoogleSheets $sheets,
        string $spreadsheetId,
        string $sheetName
    ): void {
        $startCells = [
            (string) config(
                'services.google_workspace.k302_nopol_start_cell',
                'C25'
            ),

            (string) config(
                'services.google_workspace.k302_tlpg_start_cell',
                'F25'
            ),

            (string) config(
                'services.google_workspace.k302_pc_start_cell',
                'AE25'
            ),
        ];

        foreach ($startCells as $startCell) {
            $clearRange = $this->buildSpillClearRange(
                $sheetName,
                $startCell
            );

            $sheets
                ->spreadsheets_values
                ->clear(
                    $spreadsheetId,
                    $clearRange,
                    new ClearValuesRequest()
                );
        }
    }

    private function buildSpillClearRange(
        string $sheetName,
        string $startCell
    ): string {
        $startCell = strtoupper(
            trim($startCell)
        );

        if (
            preg_match(
                '/^([A-Z]+)([1-9][0-9]*)$/',
                $startCell,
                $matches
            ) !== 1
        ) {
            throw new RuntimeException(
                'Konfigurasi sel K3-02 tidak valid: '
                . $startCell
            );
        }

        $column = $matches[1];
        $row = (int) $matches[2];

        return "'"
            . $this->escapeSheetName(
                $sheetName
            )
            . "'!"
            . $column
            . $row
            . ':'
            . $column
            . '1000';
    }

    private function buildImportRangeFormula(
        string $spreadsheetId,
        string $sheetName,
        int $columnNumber
    ): string {
        if (
            $spreadsheetId === ''
            || $sheetName === ''
        ) {
            return '';
        }

        return '=QUERY(IMPORTRANGE("'
            . $spreadsheetId
            . '";"'
            . $sheetName
            . '!A2:H");"select Col'
            . $columnNumber
            . ' where Col3 is not null";0)';
    }

    /**
     * Menjamin payload Google Sheets berbentuk array dua dimensi murni.
     * Nilai NULL diubah menjadi string kosong dan seluruh string dibersihkan
     * menjadi UTF-8 valid agar tidak merusak serialisasi JSON Google API.
     *
     * @param array<int, array<int, mixed>> $rows
     * @return array<int, array<int, string|int|float>>
     */
    private function normalizeSheetRows(
        array $rows
    ): array {
        $normalizedRows = [];

        foreach (array_values($rows) as $row) {
            $normalizedRow = [];

            foreach (array_values($row) as $value) {
                $normalizedRow[] =
                    $this->normalizeSheetCell(
                        $value
                    );
            }

            $normalizedRows[] =
                $normalizedRow;
        }

        return $normalizedRows;
    }

    private function normalizeSheetCell(
        mixed $value
    ): string|int|float {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value
                ? 'TRUE'
                : 'FALSE';
        }

        if (
            is_int($value)
            || is_float($value)
        ) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            $encoded = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
            );

            $value = $encoded !== false
                ? $encoded
                : '';
        }

        $value = str_replace(
            "\0",
            '',
            $value
        );

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding(
                $value,
                'UTF-8',
                'UTF-8'
            );
        }

        return $value;
    }

    private function compactExceptionMessage(
        Throwable $exception,
        string $fallback
    ): string {
        $message = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $exception->getMessage()
            )
            ?? ''
        );

        if ($message === '') {
            $message = $fallback;
        }

        if (mb_strlen($message, 'UTF-8') > 6000) {
            $message = mb_substr(
                $message,
                0,
                6000,
                'UTF-8'
            ) . '...';
        }

        return $message;
    }

    private function normalizeScopes(
        mixed $value
    ): array {
        if (is_array($value)) {
            return array_values(
                array_filter(
                    $value,
                    'is_string'
                )
            );
        }

        if (!is_string($value)) {
            return [];
        }

        return array_values(
            array_filter(
                preg_split(
                    '/\s+/',
                    trim($value)
                ) ?: []
            )
        );
    }

    private function requiredConfig(
        string $key,
        string $label
    ): string {
        $value = trim(
            (string) config(
                'services.google_workspace.'
                . $key
            )
        );

        if ($value === '') {
            throw new RuntimeException(
                $label
                . ' belum dikonfigurasi.'
            );
        }

        return $value;
    }

    private function escapeSheetName(
        string $sheetName
    ): string {
        return str_replace(
            "'",
            "''",
            $sheetName
        );
    }

    private function indonesianMonthName(
        int $month
    ): string {
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
            default => throw new RuntimeException(
                'Bulan tidak valid.'
            ),
        };
    }
}
