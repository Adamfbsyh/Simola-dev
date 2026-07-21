<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InspectK3061Sheet extends Command
{
    protected $signature = 'k3061:inspect
                            {--rows=60 : Jumlah baris awal yang diperiksa}
                            {--columns=AZ : Kolom terakhir yang diperiksa}';

    protected $description =
        'Memeriksa struktur sheet K3-06.1 Daily sebelum sinkronisasi';

    public function handle(): int
    {
        try {
            $spreadsheetId = trim(
                (string) config(
                    'services.k3061.spreadsheet_id'
                )
            );

            $expectedSheetName = trim(
                (string) config(
                    'services.k3061.sheet_name',
                    'K3-06.1 Daily'
                )
            );

            if ($spreadsheetId === '') {
                throw new RuntimeException(
                    'K3061_SPREADSHEET_ID belum diisi pada file .env.'
                );
            }

            $rowLimit = max(
                1,
                min(
                    (int) $this->option('rows'),
                    500
                )
            );

            $lastColumn = mb_strtoupper(
                trim(
                    (string) $this->option('columns')
                ),
                'UTF-8'
            );

            if (
                !preg_match(
                    '/^[A-Z]{1,3}$/',
                    $lastColumn
                )
            ) {
                throw new RuntimeException(
                    'Nilai --columns tidak valid. Contoh: AZ atau ZZ.'
                );
            }

            $credentialsPath =
                $this->resolveCredentialsPath();

            $this->newLine();

            $this->info(
                'Memeriksa Google Spreadsheet K3-06.1...'
            );

            $this->line(
                'Credential: ' .
                $credentialsPath
            );

            $client = new GoogleClient();

            $client->setApplicationName(
                'SIMOLA K3-06.1 Inspector'
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

            /*
            |--------------------------------------------------------------------------
            | Ambil metadata spreadsheet
            |--------------------------------------------------------------------------
            */

            $spreadsheet = $service
                ->spreadsheets
                ->get(
                    $spreadsheetId,
                    [
                        'fields' =>
                            'properties.title,sheets.properties',
                    ]
                );

            $spreadsheetTitle = (string) (
                $spreadsheet
                    ->getProperties()
                    ?->getTitle()
                ?? '-'
            );

            $sheetMetadata = collect(
                $spreadsheet->getSheets()
            )
                ->map(function ($sheet) {
                    $properties =
                        $sheet->getProperties();

                    return [
                        'sheet_id' =>
                            $properties->getSheetId(),

                        'title' =>
                            (string) $properties->getTitle(),

                        'row_count' =>
                            $properties
                                ->getGridProperties()
                                ?->getRowCount(),

                        'column_count' =>
                            $properties
                                ->getGridProperties()
                                ?->getColumnCount(),
                    ];
                })
                ->values();

            $this->newLine();

            $this->info(
                'Spreadsheet: ' .
                $spreadsheetTitle
            );

            $this->table(
                [
                    'Sheet ID',
                    'Nama Sheet',
                    'Jumlah Baris',
                    'Jumlah Kolom',
                ],
                $sheetMetadata
                    ->map(function (array $sheet) {
                        return [
                            $sheet['sheet_id'],
                            $sheet['title'],
                            $sheet['row_count'],
                            $sheet['column_count'],
                        ];
                    })
                    ->all()
            );

            /*
            |--------------------------------------------------------------------------
            | Cari sheet yang sesuai
            |--------------------------------------------------------------------------
            */

            $targetSheet = $sheetMetadata
                ->first(function (array $sheet) use (
                    $expectedSheetName
                ) {
                    return $this->normalizeText(
                        $sheet['title']
                    ) === $this->normalizeText(
                        $expectedSheetName
                    );
                });

            if (!$targetSheet) {
                $targetSheet = $sheetMetadata
                    ->first(function (array $sheet) {
                        $title =
                            $this->normalizeText(
                                $sheet['title']
                            );

                        return str_contains(
                            $title,
                            'K3 06 1'
                        )
                        &&
                        str_contains(
                            $title,
                            'DAILY'
                        );
                    });
            }

            if (!$targetSheet) {
                throw new RuntimeException(
                    'Sheet "' .
                    $expectedSheetName .
                    '" tidak ditemukan. Periksa daftar sheet di atas.'
                );
            }

            $actualSheetName =
                $targetSheet['title'];

            $escapedSheetName =
                str_replace(
                    "'",
                    "''",
                    $actualSheetName
                );

            $range =
                "'" .
                $escapedSheetName .
                "'!A1:" .
                $lastColumn .
                $rowLimit;

            $this->newLine();

            $this->info(
                'Membaca range: ' .
                $range
            );

            /*
            |--------------------------------------------------------------------------
            | Ambil nilai sel
            |--------------------------------------------------------------------------
            */

            $response = $service
                ->spreadsheets_values
                ->get(
                    $spreadsheetId,
                    $range,
                    [
                        'majorDimension' => 'ROWS',

                        'valueRenderOption' =>
                            'FORMATTED_VALUE',

                        'dateTimeRenderOption' =>
                            'FORMATTED_STRING',
                    ]
                );

            $values =
                $response->getValues()
                ?? [];

            if (empty($values)) {
                throw new RuntimeException(
                    'Sheet ditemukan tetapi tidak berisi nilai pada range ' .
                    $range
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Tampilkan setiap baris yang berisi nilai
            |--------------------------------------------------------------------------
            */

            $previewRows = [];

            foreach ($values as $rowIndex => $row) {
                $cells = [];

                foreach ($row as $columnIndex => $value) {
                    $value = trim(
                        (string) $value
                    );

                    if ($value === '') {
                        continue;
                    }

                    $columnLetter =
                        $this->columnLetter(
                            $columnIndex + 1
                        );

                    $cells[] =
                        $columnLetter .
                        '="' .
                        str_replace(
                            [
                                "\r",
                                "\n",
                                '"',
                            ],
                            [
                                ' ',
                                ' ',
                                "'",
                            ],
                            $value
                        ) .
                        '"';
                }

                if (empty($cells)) {
                    continue;
                }

                $previewRows[] = [
                    'row_number' =>
                        $rowIndex + 1,

                    'cells' =>
                        implode(
                            ' | ',
                            $cells
                        ),
                ];
            }

            $this->newLine();

            $this->info(
                'Isi baris yang terdeteksi:'
            );

            foreach ($previewRows as $previewRow) {
                $this->line(
                    str_pad(
                        'ROW ' .
                        $previewRow['row_number'],
                        10
                    ) .
                    ': ' .
                    $previewRow['cells']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Cari kandidat header
            |--------------------------------------------------------------------------
            */

            $headerCandidates = collect(
                $previewRows
            )
                ->filter(function (array $row) {
                    $text = $this->normalizeText(
                        $row['cells']
                    );

                    return $this->containsAny(
                        $text,
                        [
                            'NOPOL',
                            'NO POL',
                            'NOMOR POLISI',
                            'PENGEMUDI',
                            'NAMA AMT',
                            'DRIVER',
                            'TANGGAL',
                            'TLPG',
                            'TERMINAL',
                        ]
                    );
                })
                ->values();

            $this->newLine();

            if ($headerCandidates->isEmpty()) {
                $this->warn(
                    'Belum ditemukan kandidat header NOPOL/AMT/TANGGAL pada baris awal.'
                );

                $this->warn(
                    'Coba inspeksi lebih banyak baris atau kolom.'
                );
            } else {
                $this->info(
                    'Kandidat header:'
                );

                foreach ($headerCandidates as $candidate) {
                    $this->line(
                        'ROW ' .
                        $candidate['row_number'] .
                        ': ' .
                        $candidate['cells']
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan hasil inspeksi ke JSON
            |--------------------------------------------------------------------------
            */

            $outputDirectory =
                storage_path(
                    'app/k3061'
                );

            if (!is_dir($outputDirectory)) {
                mkdir(
                    $outputDirectory,
                    0775,
                    true
                );
            }

            $outputPath =
                $outputDirectory .
                DIRECTORY_SEPARATOR .
                'k3061-inspect.json';

            $output = [
                'generated_at' =>
                    now()->toDateTimeString(),

                'spreadsheet_id' =>
                    $spreadsheetId,

                'spreadsheet_title' =>
                    $spreadsheetTitle,

                'expected_sheet_name' =>
                    $expectedSheetName,

                'actual_sheet_name' =>
                    $actualSheetName,

                'range' =>
                    $range,

                'sheets' =>
                    $sheetMetadata->all(),

                'values' =>
                    $values,

                'preview_rows' =>
                    $previewRows,

                'header_candidates' =>
                    $headerCandidates->all(),
            ];

            file_put_contents(
                $outputPath,
                json_encode(
                    $output,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );

            $this->newLine();

            $this->info(
                'Inspeksi selesai.'
            );

            $this->line(
                'File hasil: ' .
                $outputPath
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();

            $this->error(
                $e->getMessage()
            );

            report($e);

            return self::FAILURE;
        }
    }

    private function resolveCredentialsPath(): string
    {
        $candidates = [
            config(
                'services.google_sheets.credentials'
            ),

            config(
                'services.google.credentials'
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

            'service-account.json',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim(
                (string) $candidate
            );

            if ($candidate === '') {
                continue;
            }

            /*
             * Path absolut Windows atau Linux.
             */
            if (
                preg_match(
                    '/^[A-Za-z]:[\\\\\/]/',
                    $candidate
                )
                ||
                str_starts_with(
                    $candidate,
                    DIRECTORY_SEPARATOR
                )
            ) {
                if (is_file($candidate)) {
                    return realpath($candidate)
                        ?: $candidate;
                }

                continue;
            }

            $possiblePaths = [
                base_path($candidate),

                storage_path(
                    'app/' .
                    ltrim(
                        str_replace(
                            [
                                'storage/app/',
                                'storage\\app\\',
                            ],
                            '',
                            $candidate
                        ),
                        '/\\'
                    )
                ),
            ];

            foreach ($possiblePaths as $possiblePath) {
                if (is_file($possiblePath)) {
                    return realpath(
                        $possiblePath
                    ) ?: $possiblePath;
                }
            }
        }

        throw new RuntimeException(
            'File service account Google tidak ditemukan. ' .
            'Periksa GOOGLE_SHEETS_CREDENTIALS atau lokasi JSON service account.'
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

    private function containsAny(
        string $text,
        array $needles
    ): bool {
        foreach ($needles as $needle) {
            if (
                str_contains(
                    $text,
                    $this->normalizeText(
                        $needle
                    )
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function columnLetter(
        int $columnNumber
    ): string {
        $letter = '';

        while ($columnNumber > 0) {
            $remainder =
                ($columnNumber - 1)
                % 26;

            $letter =
                chr(
                    65 + $remainder
                ) .
                $letter;

            $columnNumber =
                intdiv(
                    $columnNumber - 1,
                    26
                );
        }

        return $letter;
    }
}