<?php

namespace App\Services;

use App\Models\DriverDailyAssignment;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class K3061DailyService
{
    private string $spreadsheetId;

    private string $sheetName;

    public function __construct()
    {
        $this->spreadsheetId = trim(
            (string) config(
                'services.k3061.spreadsheet_id'
            )
        );

        $this->sheetName = trim(
            (string) config(
                'services.k3061.sheet_name',
                'K3-06.1 Daily'
            )
        );

        if ($this->spreadsheetId === '') {
            throw new RuntimeException(
                'K3061_SPREADSHEET_ID belum diisi pada file .env.'
            );
        }
    }

    /**
     * Sinkronisasi seluruh data K3-06.1 Daily.
     */
    public function sync(): array
    {
        $sheetResult = $this->readSheet();

        $parsedResult = $this->parseSheet(
            $sheetResult['values']
        );

        $rows = collect(
            $parsedResult['rows']
        );

        if ($rows->isEmpty()) {
            throw new RuntimeException(
                'Tidak ada data pengemudi yang berhasil dibaca dari sheet K3-06.1 Daily.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Hilangkan duplikasi persis antara blok history dan daily
        |--------------------------------------------------------------------------
        |
        | Blok daily diprioritaskan apabila data persis sama juga terdapat
        | pada blok history.
        |
        */

        $rows = $rows
            ->sortBy(function (array $row) {
                return $row['source_block'] === 'daily'
                    ? 0
                    : 1;
            })
            ->unique(function (array $row) {
                return implode('|', [
                    $row['source_date'],
                    $row['driver_name'],
                    $row['total_distance'] ?? '',
                    $row['travel_seconds'] ?? '',
                    $row['stop_seconds'] ?? '',
                ]);
            })
            ->values();

        $now = now();

        $databaseRows = $rows
            ->map(function (array $row) use ($now) {
                return [
                    'source_date' =>
                        $row['source_date'],

                    /*
                     * Sheet K3-06.1 tidak memiliki NOPOL.
                     */
                    'nopol' =>
                        null,

                    'driver_name' =>
                        $row['driver_name'],

                    'total_distance' =>
                        $row['total_distance'],

                    'travel_seconds' =>
                        $row['travel_seconds'],

                    'stop_seconds' =>
                        $row['stop_seconds'],

                    /*
                     * Sheet tidak memiliki TLPG.
                     */
                    'tlpg' =>
                        null,

                    'source_row' =>
                        $row['source_row'],

                    'source_block' =>
                        $row['source_block'],

                    'raw_data' =>
                        json_encode(
                            $row['raw_data'],
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ),

                    'synced_at' =>
                        $now,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Ganti data lama dengan data terbaru
        |--------------------------------------------------------------------------
        |
        | Tabel ini memang khusus menyimpan hasil sinkron K3-06.1,
        | sehingga aman dibersihkan sebelum insert ulang.
        |
        */

        DB::transaction(function () use ($databaseRows) {
            DriverDailyAssignment::query()
                ->delete();

            foreach (
                $databaseRows->chunk(500)
                as $chunk
            ) {
                DriverDailyAssignment::query()
                    ->insert(
                        $chunk->all()
                    );
            }
        });

        return [
            'spreadsheet_title' =>
                $sheetResult['spreadsheet_title'],

            'sheet_name' =>
                $sheetResult['sheet_name'],

            'rows_read' =>
                count($sheetResult['values']),

            'rows_parsed' =>
                count($parsedResult['rows']),

            'rows_saved' =>
                $databaseRows->count(),

            'rows_skipped' =>
                $parsedResult['rows_skipped'],

            'history_saved' =>
                $databaseRows
                    ->where(
                        'source_block',
                        'history'
                    )
                    ->count(),

            'daily_saved' =>
                $databaseRows
                    ->where(
                        'source_block',
                        'daily'
                    )
                    ->count(),

            'latest_date' =>
                $databaseRows
                    ->max('source_date'),

            'synced_at' =>
                $now->toDateTimeString(),
        ];
    }

    /**
     * Membaca Google Spreadsheet.
     */
    private function readSheet(): array
    {
        $credentialsPath =
            $this->resolveCredentialsPath();

        $client = new GoogleClient();

        $client->setApplicationName(
            'SIMOLA K3-06.1 Daily Sync'
        );

        $client->setAuthConfig(
            $credentialsPath
        );

        $client->setScopes([
            Sheets::SPREADSHEETS_READONLY,
        ]);

        $service = new Sheets(
            $client
        );

        $spreadsheet = $service
            ->spreadsheets
            ->get(
                $this->spreadsheetId
            );

        $spreadsheetTitle = (string) (
            $spreadsheet
                ->getProperties()
                ?->getTitle()
            ?? '-'
        );

        $availableSheets = collect(
            $spreadsheet->getSheets()
        )
            ->map(function ($sheet) {
                return (string) $sheet
                    ->getProperties()
                    ->getTitle();
            })
            ->values();

        $actualSheetName = $availableSheets
            ->first(function (string $title) {
                return $this->normalizeText(
                    $title
                ) === $this->normalizeText(
                    $this->sheetName
                );
            });

        /*
         * Fallback jika nama memiliki perbedaan spasi/tanda baca.
         */
        if (!$actualSheetName) {
            $actualSheetName = $availableSheets
                ->first(function (string $title) {
                    $normalized =
                        $this->normalizeText(
                            $title
                        );

                    return str_contains(
                        $normalized,
                        'K3 06 1'
                    )
                    &&
                    str_contains(
                        $normalized,
                        'DAILY'
                    );
                });
        }

        if (!$actualSheetName) {
            throw new RuntimeException(
                'Sheet "' .
                $this->sheetName .
                '" tidak ditemukan. Sheet yang tersedia: ' .
                $availableSheets->implode(', ')
            );
        }

        $escapedSheetName = str_replace(
            "'",
            "''",
            $actualSheetName
        );

        /*
         * Struktur sheet hanya sampai kolom M:
         *
         * A-F = history
         * G   = pemisah
         * H-M = daily
         */
        $range =
            "'" .
            $escapedSheetName .
            "'!A:M";

        $response = $service
            ->spreadsheets_values
            ->get(
                $this->spreadsheetId,
                $range,
                [
                    'majorDimension' =>
                        'ROWS',

                    'valueRenderOption' =>
                        'FORMATTED_VALUE',

                    'dateTimeRenderOption' =>
                        'FORMATTED_STRING',
                ]
            );

        return [
            'spreadsheet_title' =>
                $spreadsheetTitle,

            'sheet_name' =>
                $actualSheetName,

            'values' =>
                $response->getValues()
                ?? [],
        ];
    }

    /**
     * Membaca blok A-F dan H-M.
     */
    private function parseSheet(
        array $values
    ): array {
        $blocks = [
            [
                'name' => 'history',
                'start_column' => 0,
            ],
            [
                'name' => 'daily',
                'start_column' => 7,
            ],
        ];

        $rows = [];

        $rowsSkipped = 0;

        foreach ($blocks as $block) {
            $lastDate = null;

            $monthYearContext = null;

            foreach (
                $values
                as $rowIndex => $sheetRow
            ) {
                $cells = array_pad(
                    array_slice(
                        $sheetRow,
                        $block['start_column'],
                        6
                    ),
                    6,
                    ''
                );

                $cells = array_map(
                    fn ($value) => trim(
                        (string) $value
                    ),
                    $cells
                );

                /*
                 * Kolom blok:
                 *
                 * 0 = Tanggal
                 * 1 = No
                 * 2 = Pengemudi
                 * 3 = Total Jarak Tempuh
                 * 4 = Total Waktu Perjalanan
                 * 5 = Total Waktu Berhenti
                 */

                if (
                    collect($cells)
                        ->filter(
                            fn ($value) =>
                                $value !== ''
                        )
                        ->isEmpty()
                ) {
                    continue;
                }

                $detectedMonthYear =
                    $this->detectMonthYear(
                        $cells
                    );

                if ($detectedMonthYear) {
                    $monthYearContext =
                        $detectedMonthYear;
                }

                $parsedDate = $this->parseDate(
                    $cells[0],
                    $monthYearContext
                );

                if ($parsedDate) {
                    $lastDate =
                        $parsedDate;
                }

                $driverName =
                    $this->normalizeDriverName(
                        $cells[2]
                    );

                /*
                 * Lewati header, judul, subtotal, dan baris kosong.
                 */
                if (
                    !$this->isValidDriverName(
                        $driverName
                    )
                ) {
                    continue;
                }

                $sourceDate =
                    $parsedDate
                    ?? $lastDate;

                if (!$sourceDate) {
                    $rowsSkipped++;

                    continue;
                }

                $rows[] = [
                    'source_date' =>
                        $sourceDate->toDateString(),

                    'driver_name' =>
                        $driverName,

                    'total_distance' =>
                        $this->parseDistance(
                            $cells[3]
                        ),

                    'travel_seconds' =>
                        $this->parseDuration(
                            $cells[4]
                        ),

                    'stop_seconds' =>
                        $this->parseDuration(
                            $cells[5]
                        ),

                    'source_row' =>
                        $rowIndex + 1,

                    'source_block' =>
                        $block['name'],

                    'raw_data' => [
                        'block' =>
                            $block['name'],

                        'sheet_row' =>
                            $rowIndex + 1,

                        'tanggal' =>
                            $cells[0],

                        'nomor' =>
                            $cells[1],

                        'pengemudi' =>
                            $cells[2],

                        'total_jarak_tempuh' =>
                            $cells[3],

                        'total_waktu_perjalanan' =>
                            $cells[4],

                        'total_waktu_berhenti' =>
                            $cells[5],
                    ],
                ];
            }
        }

        return [
            'rows' =>
                $rows,

            'rows_skipped' =>
                $rowsSkipped,
        ];
    }

    /**
     * Membaca tanggal dari berbagai format.
     */
    private function parseDate(
        mixed $value,
        ?array $monthYearContext = null
    ): ?Carbon {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        /*
         * Serial date Google Sheets/Excel.
         */
        if (is_numeric($value)) {
            $numericValue = (float) $value;

            if ($numericValue > 20000) {
                return Carbon::create(
                    1899,
                    12,
                    30
                )
                    ->addDays(
                        (int) $numericValue
                    )
                    ->startOfDay();
            }

            /*
             * Jika hanya angka tanggal, gunakan konteks bulan-tahun.
             */
            if (
                $numericValue >= 1
                &&
                $numericValue <= 31
                &&
                $monthYearContext
            ) {
                try {
                    return Carbon::create(
                        $monthYearContext['year'],
                        $monthYearContext['month'],
                        (int) $numericValue
                    )->startOfDay();
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        $translated =
            $this->translateIndonesianDate(
                $value
            );

        /*
         * Hilangkan nama hari.
         */
        $translated = preg_replace(
            '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\s*,?\s*/i',
            '',
            $translated
        );

        $formats = [
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'Y-m-d',
            'd/m/y',
            'd-m-y',
            'd M Y',
            'd F Y',
            'j M Y',
            'j F Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat(
                    $format,
                    $translated
                );

                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable) {
                //
            }
        }

        /*
         * Nilai hanya berisi nomor tanggal.
         */
        if (
            preg_match(
                '/^\d{1,2}$/',
                $translated
            )
            &&
            $monthYearContext
        ) {
            try {
                return Carbon::create(
                    $monthYearContext['year'],
                    $monthYearContext['month'],
                    (int) $translated
                )->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse(
                $translated
            )->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Mencari bulan dan tahun dari judul/baris sheet.
     */
    private function detectMonthYear(
        array $cells
    ): ?array {
        $text = $this->normalizeText(
            implode(
                ' ',
                $cells
            )
        );

        $months = [
            'JANUARI' => 1,
            'JANUARY' => 1,
            'JAN' => 1,

            'FEBRUARI' => 2,
            'PEBRUARI' => 2,
            'FEBRUARY' => 2,
            'FEB' => 2,

            'MARET' => 3,
            'MARCH' => 3,
            'MAR' => 3,

            'APRIL' => 4,
            'APR' => 4,

            'MEI' => 5,
            'MAY' => 5,

            'JUNI' => 6,
            'JUNE' => 6,
            'JUN' => 6,

            'JULI' => 7,
            'JULY' => 7,
            'JUL' => 7,

            'AGUSTUS' => 8,
            'AGU' => 8,
            'AGT' => 8,
            'AUGUST' => 8,
            'AUG' => 8,

            'SEPTEMBER' => 9,
            'SEP' => 9,

            'OKTOBER' => 10,
            'OCTOBER' => 10,
            'OKT' => 10,
            'OCT' => 10,

            'NOVEMBER' => 11,
            'NOV' => 11,

            'DESEMBER' => 12,
            'DECEMBER' => 12,
            'DES' => 12,
            'DEC' => 12,
        ];

        foreach ($months as $monthName => $monthNumber) {
            if (
                preg_match(
                    '/\b' .
                    preg_quote(
                        $monthName,
                        '/'
                    ) .
                    '\b.*?\b(20\d{2})\b/',
                    $text,
                    $matches
                )
            ) {
                return [
                    'month' =>
                        $monthNumber,

                    'year' =>
                        (int) $matches[1],
                ];
            }

            if (
                preg_match(
                    '/\b(20\d{2})\b.*?\b' .
                    preg_quote(
                        $monthName,
                        '/'
                    ) .
                    '\b/',
                    $text,
                    $matches
                )
            ) {
                return [
                    'month' =>
                        $monthNumber,

                    'year' =>
                        (int) $matches[1],
                ];
            }
        }

        return null;
    }

    /**
     * Konversi jarak menjadi angka desimal.
     */
    private function parseDistance(
        mixed $value
    ): ?float {
        $value = trim(
            (string) $value
        );

        if (
            $value === ''
            ||
            $value === '-'
        ) {
            return null;
        }

        $value = preg_replace(
            '/[^0-9.,\-]/',
            '',
            $value
        );

        if ($value === '') {
            return null;
        }

        /*
         * Format Indonesia: 1.234,56
         */
        if (
            str_contains($value, '.')
            &&
            str_contains($value, ',')
        ) {
            if (
                strrpos($value, ',')
                >
                strrpos($value, '.')
            ) {
                $value = str_replace(
                    '.',
                    '',
                    $value
                );

                $value = str_replace(
                    ',',
                    '.',
                    $value
                );
            } else {
                $value = str_replace(
                    ',',
                    '',
                    $value
                );
            }
        } elseif (
            str_contains(
                $value,
                ','
            )
        ) {
            $value = str_replace(
                ',',
                '.',
                $value
            );
        }

        return is_numeric($value)
            ? round(
                (float) $value,
                2
            )
            : null;
    }

    /**
     * Konversi durasi ke detik.
     */
    private function parseDuration(
        mixed $value
    ): ?int {
        $value = trim(
            (string) $value
        );

        if (
            $value === ''
            ||
            $value === '-'
        ) {
            return null;
        }

        /*
         * Format HH:MM:SS atau HH:MM.
         */
        if (
            preg_match(
                '/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/',
                $value,
                $matches
            )
        ) {
            $hours =
                (int) $matches[1];

            $minutes =
                (int) $matches[2];

            $seconds =
                isset($matches[3])
                    ? (int) $matches[3]
                    : 0;

            return
                ($hours * 3600)
                +
                ($minutes * 60)
                +
                $seconds;
        }

        /*
         * Format teks: 2 jam 15 menit 10 detik.
         */
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        $hasUnit = false;

        if (
            preg_match(
                '/(\d+)\s*(jam|hour|hours|h)\b/i',
                $value,
                $matches
            )
        ) {
            $hours = (int) $matches[1];
            $hasUnit = true;
        }

        if (
            preg_match(
                '/(\d+)\s*(menit|minute|minutes|min|m)\b/i',
                $value,
                $matches
            )
        ) {
            $minutes = (int) $matches[1];
            $hasUnit = true;
        }

        if (
            preg_match(
                '/(\d+)\s*(detik|second|seconds|sec|s)\b/i',
                $value,
                $matches
            )
        ) {
            $seconds = (int) $matches[1];
            $hasUnit = true;
        }

        if ($hasUnit) {
            return
                ($hours * 3600)
                +
                ($minutes * 60)
                +
                $seconds;
        }

        /*
         * Nilai desimal Google Sheets dapat berupa pecahan satu hari.
         */
        $numeric = str_replace(
            ',',
            '.',
            $value
        );

        if (is_numeric($numeric)) {
            $number = (float) $numeric;

            if (
                $number >= 0
                &&
                $number <= 1
            ) {
                return (int) round(
                    $number * 86400
                );
            }

            return (int) round(
                $number
            );
        }

        return null;
    }

    private function normalizeDriverName(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return '';
        }

        $value = preg_replace(
            '/^(NAMA\s*)?(AMT|DRIVER|PENGEMUDI)\s*[:\-]\s*/i',
            '',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim(
                (string) $value
            )
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }

    private function isValidDriverName(
        string $value
    ): bool {
        if ($value === '') {
            return false;
        }

        $normalized =
            $this->normalizeText(
                $value
            );

        $invalidExact = [
            'PENGEMUDI',
            'NAMA PENGEMUDI',

            'DRIVER',
            'NAMA DRIVER',

            'AMT',
            'NAMA AMT',

            'MT',
            'MT NOPOL',
            'MT NO POL',

            'NOPOL',
            'NO POL',
            'NOMOR POLISI',

            'TLPG',
            'TERMINAL',
            'TERMINALS',

            'NO',
            'TOTAL',
            'JUMLAH',
        ];

        if (
            in_array(
                $normalized,
                $invalidExact,
                true
            )
        ) {
            return false;
        }

        if (
            str_starts_with(
                $normalized,
                'TOTAL '
            )
            ||
            str_starts_with(
                $normalized,
                'JUMLAH '
            )
        ) {
            return false;
        }

        /*
         * Nama pengemudi setidaknya memiliki satu huruf.
         */
        return preg_match(
            '/[A-Z]/',
            Str::ascii(
                $normalized
            )
        ) === 1;
    }

    private function translateIndonesianDate(
        string $value
    ): string {
        return str_ireplace(
            [
                'Senin',
                'Selasa',
                'Rabu',
                'Kamis',
                'Jumat',
                "Jum'at",
                'Sabtu',
                'Minggu',

                'Januari',
                'Februari',
                'Pebruari',
                'Maret',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'Oktober',
                'Desember',
            ],
            [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Friday',
                'Saturday',
                'Sunday',

                'January',
                'February',
                'February',
                'March',
                'May',
                'June',
                'July',
                'August',
                'October',
                'December',
            ],
            $value
        );
    }

    private function normalizeText(
        mixed $value
    ): string {
        $value = Str::ascii(
            trim(
                (string) $value
            )
        );

        $value = preg_replace(
            '/[^A-Za-z0-9]+/',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim(
                (string) $value
            )
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }

    private function resolveCredentialsPath(): string
    {
        $candidates = [
            config(
                'services.google_sheets.credentials'
            ),

            env(
                'GOOGLE_SHEETS_CREDENTIALS'
            ),

            env(
                'GOOGLE_APPLICATION_CREDENTIALS'
            ),

            'storage/app/google/service-account.json',

            'storage/app/google-service-account.json',

            'storage/app/service-account.json',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim(
                (string) $candidate
            );

            if ($candidate === '') {
                continue;
            }

            /*
             * Path absolut Windows.
             */
            if (
                preg_match(
                    '/^[A-Za-z]:[\\\\\/]/',
                    $candidate
                )
            ) {
                if (is_file($candidate)) {
                    return realpath(
                        $candidate
                    ) ?: $candidate;
                }

                continue;
            }

            $cleanCandidate = preg_replace(
                '/^storage[\\\\\/]app[\\\\\/]/',
                '',
                $candidate
            );

            $possiblePaths = [
                base_path($candidate),

                storage_path(
                    'app/' .
                    ltrim(
                        (string) $cleanCandidate,
                        '/\\'
                    )
                ),
            ];

            foreach ($possiblePaths as $path) {
                if (is_file($path)) {
                    return realpath(
                        $path
                    ) ?: $path;
                }
            }
        }

        throw new RuntimeException(
            'File service account Google tidak ditemukan. ' .
            'Periksa GOOGLE_SHEETS_CREDENTIALS pada file .env.'
        );
    }
}