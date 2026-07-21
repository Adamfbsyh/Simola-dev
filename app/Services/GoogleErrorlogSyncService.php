<?php

namespace App\Services;

use App\Models\ErrorlogSheetSource;
use App\Models\MonitoringEvent;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoogleErrorlogSyncService
{
    private GoogleSheets $sheets;

    public function __construct()
    {
        $configuredPath = config(
            'services.google_sheets.credentials'
        );

        if (!$configuredPath) {
            throw new RuntimeException(
                'Lokasi credential Google Sheets belum dikonfigurasi.'
            );
        }

        $credentialsPath = $this->resolvePath(
            $configuredPath
        );

        if (!is_file($credentialsPath)) {
            throw new RuntimeException(
                'File service-account.json tidak ditemukan di: ' .
                $credentialsPath
            );
        }

        $client = new GoogleClient();

        $client->setApplicationName(
            'SIMOLA Errorlog Sync'
        );

        $client->setAuthConfig(
            $credentialsPath
        );

        $client->setScopes([
            GoogleSheets::SPREADSHEETS_READONLY,
        ]);

        $this->sheets = new GoogleSheets(
            $client
        );
    }

    public function sync(
        ErrorlogSheetSource $source
    ): array {
        /*
         * Nama tab akan diperiksa terlebih dahulu.
         *
         * Contoh yang dapat dikenali:
         * Error Log System
         * Error_Log
         * Error Log
         * ERRORLOG
         */
        $resolvedSheetName = $this->resolveSheetName(
            $source->spreadsheet_id,
            $source->sheet_name
        );

        /*
         * Simpan nama tab asli apabila berbeda dari input.
         */
        if ($source->sheet_name !== $resolvedSheetName) {
            $source->update([
                'sheet_name' => $resolvedSheetName,
            ]);

            $source->refresh();
        }

        $escapedSheetName = str_replace(
            "'",
            "''",
            $resolvedSheetName
        );

        /*
         * Ambil hingga kolom Z.
         * Posisi kolom tidak harus sama setiap bulan.
         */
        $range = "'{$escapedSheetName}'!A:Z";

        try {
            $response = $this->sheets
                ->spreadsheets_values
                ->get(
                    $source->spreadsheet_id,
                    $range,
                    [
                        'valueRenderOption' =>
                            'FORMATTED_VALUE',

                        'dateTimeRenderOption' =>
                            'FORMATTED_STRING',

                        'majorDimension' =>
                            'ROWS',
                    ]
                );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Google Spreadsheet gagal dibaca. ' .
                'Nama tab yang digunakan: "' .
                $resolvedSheetName .
                '". Pesan: ' .
                $e->getMessage()
            );
        }

        $rows = $response->getValues() ?? [];

        if (count($rows) < 2) {
            throw new RuntimeException(
                'Sheet "' .
                $resolvedSheetName .
                '" kosong atau tidak memiliki data.'
            );
        }

        /*
         * Cari posisi header dan susunan kolom secara otomatis.
         */
        [
            $headerRowIndex,
            $columns,
        ] = $this->detectHeaderAndColumns(
            $rows
        );

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $seenKeys = [];

        DB::transaction(function () use (
            $rows,
            $headerRowIndex,
            $columns,
            $source,
            &$created,
            &$updated,
            &$unchanged,
            &$skipped,
            &$seenKeys
        ) {
            for (
                $index = $headerRowIndex + 1;
                $index < count($rows);
                $index++
            ) {
                $row = array_pad(
                    $rows[$index],
                    26,
                    ''
                );

                /*
                 * Nomor baris asli Google Sheets.
                 * Index array dimulai dari nol.
                 */
                $sourceRow = $index + 1;

                $recordId = $this->cell(
                    $row,
                    $columns['record_id']
                );

                $dateRaw = $this->cell(
                    $row,
                    $columns['date']
                );

                $timeRaw = $this->cell(
                    $row,
                    $columns['time']
                );

                $shift = $this->cell(
                    $row,
                    $columns['shift']
                );

                $reporter = $this->cell(
                    $row,
                    $columns['reporter']
                );

                $pcNumber = $this->cell(
                    $row,
                    $columns['pc']
                );

                $nopol = $this->normalizeNopol(
                    $this->cell(
                        $row,
                        $columns['nopol']
                    )
                );

                $tlpg = $this->normalizeText(
                    $this->cell(
                        $row,
                        $columns['tlpg']
                    )
                );

                $remarks = $this->cell(
                    $row,
                    $columns['remarks']
                );

                $errorType = $this->cell(
                    $row,
                    $columns['error_type']
                );

                $evidence = $this->cell(
                    $row,
                    $columns['evidence']
                );

                $ticketNumber = $this->cell(
                    $row,
                    $columns['ticket']
                );

                $rtcUpdate = $this->cell(
                    $row,
                    $columns['rtc_update']
                );

                $status = $this->cell(
                    $row,
                    $columns['status']
                );

                $statusUpdatedAt = $this->cell(
                    $row,
                    $columns['status_updated_at']
                );

                /*
                 * Abaikan baris benar-benar kosong.
                 */
                if (
                    $recordId === '' &&
                    $dateRaw === '' &&
                    $timeRaw === '' &&
                    $nopol === '' &&
                    $errorType === '' &&
                    $ticketNumber === ''
                ) {
                    continue;
                }

                /*
                 * Mendukung dua pola:
                 *
                 * April:
                 * Tanggal = 2026-04-08
                 * Waktu   = 16:17
                 *
                 * Mei:
                 * Waktu   = 21/05/2026, 14:21
                 */
                $dateTime = $this->parseDateTime(
                    $dateRaw,
                    $timeRaw
                );

                if (
                    !$dateTime ||
                    $nopol === '' ||
                    $errorType === ''
                ) {
                    $skipped++;
                    continue;
                }

                /*
                 * Hanya ambil data sesuai periode yang dipilih.
                 */
                if (
                    $dateTime->year !== $source->year ||
                    $dateTime->month !== $source->month
                ) {
                    continue;
                }

                $sourceKey = $this->makeSourceKey(
                    $source,
                    $recordId,
                    $ticketNumber,
                    $dateTime,
                    $nopol,
                    $errorType,
                    $reporter
                );

                $seenKeys[] = $sourceKey;

                $event = MonitoringEvent::updateOrCreate(
                    [
                        'errorlog_source_id' =>
                            $source->id,

                        'source_key' =>
                            $sourceKey,
                    ],
                    [
                        'report_upload_id' =>
                            null,

                        'event_type' =>
                            'errorlog',

                        'event_date' =>
                            $dateTime->toDateString(),

                        'event_time' =>
                            $dateTime->format('H:i:s'),

                        'nopol' =>
                            $nopol,

                        'driver_name' =>
                            $reporter ?: null,

                        'tlpg' =>
                            $tlpg ?: null,

                        'event_name' =>
                            $errorType,

                        'category' =>
                            $remarks ?: 'Error System',

                        'severity' =>
                            'sedang',

                        'score_impact' =>
                            0,

                        'source_page' =>
                            null,

                        'source_row' =>
                            $sourceRow,

                        'evidence_link' =>
                            $evidence ?: null,

                        'description' =>
                            $remarks ?: $errorType,

                        'ticket_number' =>
                            $ticketNumber ?: null,

                        'event_status' =>
                            $status
                            ?: $rtcUpdate
                            ?: null,

                        'follow_up_status' =>
                            $rtcUpdate ?: null,

                        'raw_data' => json_encode(
                            [
                                'record_id' =>
                                    $recordId,

                                'tanggal' =>
                                    $dateRaw,

                                'waktu' =>
                                    $timeRaw,

                                'datetime_parsed' =>
                                    $dateTime->format(
                                        'Y-m-d H:i:s'
                                    ),

                                'shift' =>
                                    $shift,

                                'pelapor_operator' =>
                                    $reporter,

                                'pc' =>
                                    $pcNumber,

                                'nopol' =>
                                    $nopol,

                                'tlpg' =>
                                    $tlpg,

                                'remarks' =>
                                    $remarks,

                                'jenis_error' =>
                                    $errorType,

                                'evidence' =>
                                    $evidence,

                                'ticket_number' =>
                                    $ticketNumber,

                                'update_rtc' =>
                                    $rtcUpdate,

                                'status' =>
                                    $status,

                                'tanggal_update_status' =>
                                    $statusUpdatedAt,

                                'spreadsheet_id' =>
                                    $source->spreadsheet_id,

                                'sheet_name' =>
                                    $source->sheet_name,

                                'source_row' =>
                                    $sourceRow,
                            ],
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]
                );

                if ($event->wasRecentlyCreated) {
                    $created++;
                } elseif ($event->wasChanged()) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            $uniqueKeys = array_values(
                array_unique($seenKeys)
            );

            if (empty($uniqueKeys)) {
                throw new RuntimeException(
                    'Tidak ada data Errorlog valid untuk periode ' .
                    str_pad(
                        (string) $source->month,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ) .
                    '-' .
                    $source->year .
                    '. Pastikan bulan laporan dan isi spreadsheet sesuai.'
                );
            }

            /*
             * Hapus record yang sudah tidak ada di spreadsheet.
             */
            MonitoringEvent::query()
                ->where(
                    'errorlog_source_id',
                    $source->id
                )
                ->whereNotIn(
                    'source_key',
                    $uniqueKeys
                )
                ->delete();

            $source->update([
                'last_synced_at' =>
                    now(),

                'total_rows' =>
                    count($uniqueKeys),

                'created_rows' =>
                    $created,

                'updated_rows' =>
                    $updated,

                'status' =>
                    'berhasil',

                'last_error' =>
                    null,
            ]);
        });

        return [
            'created' =>
                $created,

            'updated' =>
                $updated,

            'unchanged' =>
                $unchanged,

            'skipped' =>
                $skipped,

            'total' =>
                count(
                    array_unique($seenKeys)
                ),

            'sheet_name' =>
                $resolvedSheetName,
        ];
    }

    /**
     * Mencari nama tab yang benar.
     */
    private function resolveSheetName(
        string $spreadsheetId,
        string $requestedName
    ): string {
        try {
            $spreadsheet = $this->sheets
                ->spreadsheets
                ->get(
                    $spreadsheetId,
                    [
                        'fields' =>
                            'sheets.properties.title',
                    ]
                );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Daftar sheet gagal dibaca. ' .
                'Pastikan file sudah berupa Google Spreadsheet asli, ' .
                'bukan file XLSX. Pesan: ' .
                $e->getMessage()
            );
        }

        $titles = [];

        foreach (
            $spreadsheet->getSheets() ?? []
            as $sheet
        ) {
            $title = $sheet
                ->getProperties()
                ->getTitle();

            if ($title !== null && $title !== '') {
                $titles[] = $title;
            }
        }

        if (empty($titles)) {
            throw new RuntimeException(
                'Tidak ada tab yang ditemukan pada spreadsheet.'
            );
        }

        /*
         * Prioritas 1: nama sama persis.
         */
        foreach ($titles as $title) {
            if (
                strcasecmp(
                    trim($title),
                    trim($requestedName)
                ) === 0
            ) {
                return $title;
            }
        }

        /*
         * Prioritas 2: nama sama setelah spasi,
         * tanda minus, dan underscore dibuang.
         */
        $requestedNormalized =
            $this->normalizeHeader(
                $requestedName
            );

        foreach ($titles as $title) {
            if (
                $this->normalizeHeader($title)
                === $requestedNormalized
            ) {
                return $title;
            }
        }

        /*
         * Prioritas 3: cari satu-satunya tab
         * yang mengandung kata ERRORLOG.
         *
         * Contoh:
         * Error_Log
         * Error Log System
         */
        $errorlogTitles = array_values(
            array_filter(
                $titles,
                fn (string $title) =>
                    str_contains(
                        $this->normalizeHeader($title),
                        'errorlog'
                    )
            )
        );

        if (count($errorlogTitles) === 1) {
            return $errorlogTitles[0];
        }

        throw new RuntimeException(
            'Nama sheet "' .
            $requestedName .
            '" tidak ditemukan. Sheet yang tersedia: ' .
            implode(', ', $titles)
        );
    }

    /**
     * Mencari header dan posisi kolom.
     */
    private function detectHeaderAndColumns(
        array $rows
    ): array {
        $maximumHeaderRows = min(
            20,
            count($rows)
        );

        for (
            $rowIndex = 0;
            $rowIndex < $maximumHeaderRows;
            $rowIndex++
        ) {
            $headers = array_map(
                fn ($value) =>
                    $this->normalizeHeader($value),
                $rows[$rowIndex]
            );

            $nopolIndex = $this->findHeaderIndex(
                $headers,
                [
                    'nopol',
                    'nopolis',
                    'nomorpolisi',
                    'nopolkendaraan',
                ]
            );

            $errorTypeIndex = $this->findHeaderIndex(
                $headers,
                [
                    'jeniserror',
                    'jeniserrorsystem',
                    'jeniserrorsistem',
                    'jeniserrorsystemketeranganerrorsystem',
                    'keteranganerrorsystem',
                    'keteranganerrorsistem',
                ]
            );

            $dateIndex = $this->findHeaderIndex(
                $headers,
                [
                    'tanggal',
                    'tanggalkejadian',
                    'date',
                ]
            );

            $timeIndex = $this->findHeaderIndex(
                $headers,
                [
                    'waktu',
                    'waktukejadian',
                    'datetime',
                    'tanggalwaktu',
                ]
            );

            if (
                $nopolIndex === null ||
                $errorTypeIndex === null ||
                (
                    $dateIndex === null &&
                    $timeIndex === null
                )
            ) {
                continue;
            }

            return [
                $rowIndex,

                [
                    'record_id' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'iderror',
                                'id',
                                'nomor',
                                'no',
                            ]
                        ),

                    'date' =>
                        $dateIndex,

                    'time' =>
                        $timeIndex,

                    'shift' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'shift',
                            ]
                        ),

                    'reporter' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'pelapor',
                                'operator',
                                'namaoperator',
                            ]
                        ),

                    'pc' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'nopc',
                                'pcname',
                                'pc',
                            ]
                        ),

                    'nopol' =>
                        $nopolIndex,

                    'tlpg' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'tlpg',
                                'tlpgtbbm',
                                'terminal',
                                'tbbm',
                            ]
                        ),

                    'remarks' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'remarks',
                                'remarksketerangan',
                                'keterangan',
                            ]
                        ),

                    'error_type' =>
                        $errorTypeIndex,

                    'evidence' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'linkevidence',
                                'linkevidencegambar',
                                'evidence',
                                'linkgambar',
                            ]
                        ),

                    'ticket' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'eticket',
                                'noeticket',
                                'nomoreticket',
                                'ticketnumber',
                            ]
                        ),

                    'rtc_update' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'updatebyrtc',
                                'updatertc',
                                'statusrtc',
                            ]
                        ),

                    'status' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'statusbymceasy',
                                'status',
                            ]
                        ),

                    'status_updated_at' =>
                        $this->findHeaderIndex(
                            $headers,
                            [
                                'tanggalupdatestatus',
                                'statusupdatedat',
                            ]
                        ),
                ],
            ];
        }

        throw new RuntimeException(
            'Header Errorlog tidak berhasil dikenali. ' .
            'Kolom wajib: NOPOL, Jenis Error, serta Tanggal atau Waktu.'
        );
    }

    private function findHeaderIndex(
        array $normalizedHeaders,
        array $aliases
    ): ?int {
        foreach (
            $normalizedHeaders
            as $index => $header
        ) {
            if (
                in_array(
                    $header,
                    $aliases,
                    true
                )
            ) {
                return $index;
            }
        }

        return null;
    }

    private function cell(
        array $row,
        ?int $index
    ): string {
        if ($index === null) {
            return '';
        }

        return trim(
            (string) (
                $row[$index] ?? ''
            )
        );
    }

    private function parseDateTime(
        string $dateRaw,
        string $timeRaw
    ): ?Carbon {
        $dateRaw = trim($dateRaw);
        $timeRaw = trim($timeRaw);

        $candidates = [];

        /*
         * Format tanggal dan waktu terpisah.
         */
        if (
            $dateRaw !== '' &&
            $timeRaw !== ''
        ) {
            $candidates[] =
                $dateRaw . ' ' . $timeRaw;
        }

        /*
         * Waktu bisa sudah mengandung tanggal.
         */
        if ($timeRaw !== '') {
            $candidates[] = $timeRaw;
        }

        if ($dateRaw !== '') {
            $candidates[] = $dateRaw;
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d G:i',

            'd/m/Y, H:i:s',
            'd/m/Y, H:i',
            'd/m/Y,H:i:s',
            'd/m/Y,H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',

            'j/n/Y, G:i:s',
            'j/n/Y, G:i',
            'j/n/Y G:i:s',
            'j/n/Y G:i',

            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'j-n-Y G:i',

            'Y/m/d H:i:s',
            'Y/m/d H:i',
        ];

        foreach ($candidates as $candidate) {
            $candidate = preg_replace(
                '/\s+/',
                ' ',
                trim($candidate)
            );

            foreach ($formats as $format) {
                try {
                    $dateTime =
                        Carbon::createFromFormat(
                            $format,
                            $candidate
                        );

                    if ($dateTime !== false) {
                        return $dateTime;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            try {
                return Carbon::parse(
                    $candidate
                );
            } catch (\Throwable $e) {
                // Coba kandidat berikutnya.
            }
        }

        return null;
    }

    private function makeSourceKey(
        ErrorlogSheetSource $source,
        string $recordId,
        string $ticketNumber,
        Carbon $dateTime,
        string $nopol,
        string $errorType,
        string $reporter
    ): string {
        /*
         * April memiliki ID_Error.
         * Gunakan ID tersebut sebagai identitas utama.
         */
        if ($recordId !== '') {
            $identity =
                'record:' .
                strtoupper($recordId);
        } elseif ($ticketNumber !== '') {
            $identity =
                'ticket:' .
                strtoupper($ticketNumber);
        } else {
            $identity = implode(
                '|',
                [
                    $dateTime->format(
                        'Y-m-d H:i:s'
                    ),

                    $nopol,

                    strtoupper($errorType),

                    strtoupper($reporter),
                ]
            );
        }

        return hash(
            'sha256',
            implode(
                '|',
                [
                    $source->spreadsheet_id,
                    $source->sheet_name,
                    $identity,
                ]
            )
        );
    }

    private function normalizeHeader(
        mixed $value
    ): string {
        $value = strtolower(
            trim((string) $value)
        );

        return preg_replace(
            '/[^a-z0-9]+/',
            '',
            $value
        );
    }

    private function normalizeNopol(
        mixed $value
    ): string {
        $value = strtoupper(
            trim((string) $value)
        );

        $value = preg_replace(
            '/[^A-Z0-9]+/',
            ' ',
            $value
        );

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                $value
            )
        );
    }

    private function normalizeText(
        mixed $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                strtoupper(
                    (string) $value
                )
            )
        );
    }

    private function resolvePath(
        string $path
    ): string {
        /*
         * Path absolut Windows.
         */
        if (
            preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $path
            )
        ) {
            return $path;
        }

        /*
         * Path absolut Linux.
         */
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}