<?php

namespace App\Services;

use App\Models\K32DailyRecord;
use App\Models\MonitoringEvent;
use App\Models\ReportUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class K32DailyService
{
    private const SPREADSHEET_ID =
        '1l8Tkur7Z7-qw7SuMhZYZMEzCSgXWXUH2twA3zikR_lY';

    private const SHEET_NAME = 'K3-2.2 DAILY';

    /**
     * Ambil data Google Sheet lalu simpan ke tabel k32_daily_records.
     *
     * Rentang tanggal otomatis mengikuti data PDF pelanggaran
     * paling awal sampai paling akhir di monitoring_events.
     */
    public function syncFromGoogleSheet(): array
    {
        $pdfStartValue = MonitoringEvent::query()
            ->where('event_type', 'pelanggaran')
            ->whereNotNull('event_date')
            ->min('event_date');

        $pdfEndValue = MonitoringEvent::query()
            ->where('event_type', 'pelanggaran')
            ->whereNotNull('event_date')
            ->max('event_date');

        if (!$pdfStartValue || !$pdfEndValue) {
            throw new RuntimeException(
                'Belum ada data PDF pelanggaran yang dapat dijadikan rentang crosscheck.'
            );
        }

        $pdfStart = Carbon::parse($pdfStartValue)
            ->startOfDay();

        $pdfEnd = Carbon::parse($pdfEndValue)
            ->endOfDay();

        $csv = $this->downloadCsv();

        $parsed = $this->parseCsv(
            $csv,
            $pdfStart,
            $pdfEnd
        );

        if (empty($parsed['vehicles'])) {
            throw new RuntimeException(
                'Tidak ada daftar kendaraan K3.2 yang berhasil dibaca pada rentang ' .
                $pdfStart->format('d-m-Y') .
                ' sampai ' .
                $pdfEnd->format('d-m-Y') .
                '. Periksa nama sheet dan struktur header.'
            );
        }

        $now = now();

        $insertRows = collect($parsed['records'])
            ->map(function (array $record) use ($now) {
                return [
                    'source_date' => $record['source_date'],
                    'nopol' => $record['nopol'],
                    'tlpg' => $record['tlpg'],
                    'event_name' => $record['event_name'],
                    'spreadsheet_count' => $record['spreadsheet_count'],
                    'source_row' => $record['source_row'],
                    'source_sheet' => self::SHEET_NAME,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values();

        DB::transaction(function () use (
            $insertRows,
            $pdfStart,
            $pdfEnd
        ) {
            /*
             * Hapus hasil sinkronisasi pada rentang PDF aktif,
             * kemudian masukkan data terbaru dari Google Sheet.
             */
            K32DailyRecord::query()
                ->whereBetween('source_date', [
                    $pdfStart->toDateString(),
                    $pdfEnd->toDateString(),
                ])
                ->delete();

            foreach ($insertRows->chunk(500) as $chunk) {
                K32DailyRecord::query()->insert(
                    $chunk->all()
                );
            }
        });

        /*
        * Setelah data K3.2 selesai disimpan,
        * otomatis baca ulang PDF pelanggaran yang jamnya masih kosong.
        */
        $pdfTimeStats = $this->syncPdfEventTimes(
            $pdfStart,
            $pdfEnd
        );

        return [
            'pdf_start' => $pdfStart->toDateString(),
            'pdf_end' => $pdfEnd->toDateString(),

            'header_row' => $parsed['header_row'],
            'rows_scanned' => $parsed['rows_scanned'],
            'nopol_rows' => $parsed['nopol_rows'],

            'vehicles_found' =>
            count($parsed['vehicles']),

            'records_saved' => $insertRows->count(),

            'total_value' => $insertRows->sum(
                'spreadsheet_count'
            ),

            'ignored_headers' => $parsed['ignored_headers'],

            /*
            * Statistik pembacaan jam PDF.
            */
            'pdf_files_checked' =>
                $pdfTimeStats['files_checked'],

            'pdf_files_updated' =>
                $pdfTimeStats['files_updated'],

            'pdf_events_updated' =>
                $pdfTimeStats['events_updated'],

            'pdf_time_not_found' =>
                $pdfTimeStats['time_not_found'],

            'pdf_file_missing' =>
                $pdfTimeStats['file_missing'],

            'pdf_read_failed' =>
                $pdfTimeStats['read_failed'],

            'pdf_already_has_time' =>
                $pdfTimeStats['already_has_time'],
        ];
    }

    /**
     * Mengambil seluruh daftar kendaraan dan pelanggaran
     * langsung dari sheet untuk kebutuhan laporan.
     *
     * Berbeda dengan crosscheck, laporan membutuhkan kendaraan
     * yang seluruh nilai pelanggarannya nol.
     */
    public function getReportRows(
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $csv = $this->downloadCsv();

        return $this->parseCsv(
            $csv,
            $startDate->copy()->startOfDay(),
            $endDate->copy()->endOfDay()
        );
    }

    /**
     * Unduh sheet sebagai CSV.
     */
    private function downloadCsv(): string
    {
        /*
        * Hanya mengambil blok kiri:
        *
        * A = Bulan dan Tahun
        * B = Tanggal
        * C = NOPOL
        * D = TLPG
        * E sampai Z = jenis pelanggaran
        *
        * headers=0 digunakan agar Google tidak membuang
        * baris header dan tidak menebak struktur tabel.
        */
        $url =
            'https://docs.google.com/spreadsheets/d/' .
            self::SPREADSHEET_ID .
            '/gviz/tq?' .
            http_build_query([
                'tqx' => 'out:csv',
                'sheet' => self::SHEET_NAME,
                'range' => 'A1:Z',
                'headers' => 0,
            ]);

        try {
            $response = Http::timeout(180)
                ->retry(2, 1000)
                ->get($url);
        } catch (\Throwable $firstError) {
            /*
            * Fallback untuk XAMPP/Windows jika sertifikat SSL bermasalah.
            */
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->retry(2, 1000)
                ->get($url);
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'Google Sheet gagal dibaca. HTTP status: ' .
                $response->status()
            );
        }

        $body = $response->body();

        $lowerBody = strtolower($body);

        if (
            str_contains($lowerBody, '<!doctype html') ||
            str_contains($lowerBody, 'accounts.google.com')
        ) {
            throw new RuntimeException(
                'Google Sheet belum dapat dibaca tanpa login. ' .
                'Pastikan akses spreadsheet: Siapa saja yang memiliki link dapat melihat.'
            );
        }

        /*
        * Hilangkan UTF-8 BOM jika ada.
        */
        $body = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $body
        );

        if (!trim($body)) {
            throw new RuntimeException(
                'Google Sheet menghasilkan data CSV kosong.'
            );
        }

        return $body;
    }

    /**
     * Membaca CSV tanpa memasukkan seluruh file ke array besar.
     */
    private function parseCsv(
            string $csv,
            Carbon $pdfStart,
            Carbon $pdfEnd
        ): array {
            $handle = fopen(
                'php://temp',
                'r+'
            );

            if (!$handle) {
                throw new RuntimeException(
                    'Gagal membuat penyimpanan sementara untuk membaca CSV.'
                );
            }

            fwrite(
                $handle,
                $csv
            );

            rewind(
                $handle
            );

            $columnConfig =
                $this->leftBlockColumnConfig();

            $rowNumber = 0;
            $rowsScanned = 0;
            $nopolRows = 0;

            /*
            * Digunakan apabila bulan atau tanggal hanya
            * tertulis pada baris pertama kelompok.
            */
            $lastMonthYear = null;
            $lastDay = null;

            /*
            * Seluruh kendaraan, termasuk yang semua
            * nilai pelanggarannya nol.
            */
            $vehicles = [];

            /*
            * Hanya record pelanggaran dengan nilai > 0.
            */
            $records = [];

            while (
                ($row = fgetcsv($handle))
                !== false
            ) {
                $rowNumber++;
                $rowsScanned++;

                $row = array_map(
                    function ($value) {
                        return is_string($value)
                            ? trim($value)
                            : $value;
                    },
                    $row
                );

                $monthYearRaw = trim(
                    (string) (
                        $row[
                            $columnConfig[
                                'month_year_index'
                            ]
                        ]
                        ?? ''
                    )
                );

                $dayRaw = trim(
                    (string) (
                        $row[
                            $columnConfig[
                                'day_index'
                            ]
                        ]
                        ?? ''
                    )
                );

                $nopolRaw = trim(
                    (string) (
                        $row[
                            $columnConfig[
                                'nopol_index'
                            ]
                        ]
                        ?? ''
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Abaikan header
                |--------------------------------------------------------------------------
                */
                $normalizedMonthHeader =
                    $this->normalizeHeader(
                        $monthYearRaw
                    );

                $normalizedDayHeader =
                    $this->normalizeHeader(
                        $dayRaw
                    );

                $normalizedNopolHeader =
                    $this->normalizeHeader(
                        $nopolRaw
                    );

                if (
                    $normalizedMonthHeader
                        === 'BULAN DAN TAHUN'
                    ||
                    $normalizedDayHeader
                        === 'TANGGAL'
                    ||
                    $normalizedNopolHeader
                        === 'NOPOL'
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Simpan bulan dan tanggal terakhir
                |--------------------------------------------------------------------------
                */
                if ($monthYearRaw !== '') {
                    $lastMonthYear =
                        $monthYearRaw;
                }

                if ($dayRaw !== '') {
                    $lastDay =
                        $dayRaw;
                }

                if ($nopolRaw === '') {
                    continue;
                }

                $nopol =
                    $this->normalizeNopol(
                        $nopolRaw
                    );

                /*
                * NOPOL harus mengandung angka.
                */
                if (
                    !$nopol ||
                    !preg_match(
                        '/\d/',
                        $nopol
                    )
                ) {
                    continue;
                }

                $sourceDate =
                    $this->parseSourceDate(
                        $lastMonthYear,
                        $lastDay
                    );

                if (!$sourceDate) {
                    continue;
                }

                /*
                * Batasi data sesuai periode laporan
                * atau periode crosscheck.
                */
                if (
                    $sourceDate->lt(
                        $pdfStart
                    )
                    ||
                    $sourceDate->gt(
                        $pdfEnd
                    )
                ) {
                    continue;
                }

                $nopolRows++;

                $tlpgRaw =
                    $row[
                        $columnConfig[
                            'tlpg_index'
                        ]
                    ]
                    ?? null;

                $tlpg =
                    $this->normalizeTlpg(
                        $tlpgRaw
                    );

                /*
                |--------------------------------------------------------------------------
                | Simpan seluruh kendaraan
                |--------------------------------------------------------------------------
                |
                | Kendaraan tetap disimpan meskipun semua
                | nilai pelanggarannya kosong atau nol.
                |
                */
                $vehicleKey = implode(
                    '|',
                    [
                        $sourceDate
                            ->toDateString(),

                        $nopol,
                    ]
                );

                if (
                    !isset(
                        $vehicles[$vehicleKey]
                    )
                ) {
                    $vehicles[$vehicleKey] = [
                        'source_date' =>
                            $sourceDate
                                ->toDateString(),

                        'nopol' =>
                            $nopol,

                        'tlpg' =>
                            $tlpg,

                        'source_row' =>
                            $rowNumber,
                    ];
                } else {
                    /*
                    * Jika sebelumnya TLPG kosong, gunakan
                    * TLPG dari baris berikutnya.
                    */
                    if (
                        !$vehicles[$vehicleKey]
                            ['tlpg']
                        &&
                        $tlpg
                    ) {
                        $vehicles[$vehicleKey]
                            ['tlpg'] =
                                $tlpg;
                    }

                    /*
                    * Simpan posisi baris terbaru.
                    */
                    $vehicles[$vehicleKey]
                        ['source_row'] =
                            $rowNumber;
                }

                /*
                |--------------------------------------------------------------------------
                | Simpan nilai pelanggaran
                |--------------------------------------------------------------------------
                */
                foreach (
                    $columnConfig[
                        'event_columns'
                    ]
                    as $columnIndex => $eventName
                ) {
                    $count =
                        $this->parseCount(
                            $row[$columnIndex]
                            ?? null
                        );

                    /*
                    * Nilai nol tidak perlu menjadi record
                    * pelanggaran, tetapi kendaraannya sudah
                    * disimpan di array $vehicles.
                    */
                    if ($count <= 0) {
                        continue;
                    }

                    $recordKey = implode(
                        '|',
                        [
                            $sourceDate
                                ->toDateString(),

                            $nopol,

                            $eventName,
                        ]
                    );

                    if (
                        !isset(
                            $records[$recordKey]
                        )
                    ) {
                        $records[$recordKey] = [
                            'source_date' =>
                                $sourceDate
                                    ->toDateString(),

                            'nopol' =>
                                $nopol,

                            'tlpg' =>
                                $tlpg,

                            'event_name' =>
                                $eventName,

                            'spreadsheet_count' =>
                                0,

                            'source_row' =>
                                $rowNumber,
                        ];
                    }

                    /*
                    * Jika kombinasi tanggal, NOPOL dan
                    * pelanggaran muncul lebih dari sekali,
                    * jumlahkan nilainya.
                    */
                    $records[$recordKey]
                        ['spreadsheet_count'] +=
                            $count;

                    if (
                        !$records[$recordKey]
                            ['tlpg']
                        &&
                        $tlpg
                    ) {
                        $records[$recordKey]
                            ['tlpg'] =
                                $tlpg;
                    }
                }
            }

            fclose(
                $handle
            );

            return [
                /*
                * Seluruh kendaraan dalam periode.
                */
                'vehicles' =>
                    array_values(
                        $vehicles
                    ),

                /*
                * Hanya pelanggaran bernilai lebih dari nol.
                */
                'records' =>
                    array_values(
                        $records
                    ),

                'header_row' =>
                    7,

                'rows_scanned' =>
                    $rowsScanned,

                'nopol_rows' =>
                    $nopolRows,

                'ignored_headers' =>
                    [],
            ];
        }

    /**
     * Cari header pada blok kiri.
     *
     * Pembacaan berhenti sebelum header NOPOL kedua
     * agar blok kanan tidak ikut terbaca.
     */
    // private function detectHeaderRow(
    //     array $row
    // ): ?array {
    //     $normalizedHeaders = array_map(
    //         fn ($value) =>
    //             $this->normalizeHeader($value),
    //         $row
    //     );

    //     $monthYearIndex = array_search(
    //         'BULAN DAN TAHUN',
    //         $normalizedHeaders,
    //         true
    //     );

    //     $dayIndex = array_search(
    //         'TANGGAL',
    //         $normalizedHeaders,
    //         true
    //     );

    //     $nopolIndex = array_search(
    //         'NOPOL',
    //         $normalizedHeaders,
    //         true
    //     );

    //     if (
    //         $monthYearIndex === false ||
    //         $dayIndex === false ||
    //         $nopolIndex === false
    //     ) {
    //         return null;
    //     }

    //     /*
    //      * Cari NOPOL kedua sebagai awal blok kanan.
    //      */
    //     $rightBlockIndex = null;

    //     for (
    //         $index = $nopolIndex + 1;
    //         $index < count($normalizedHeaders);
    //         $index++
    //     ) {
    //         if (
    //             $normalizedHeaders[$index]
    //             === 'NOPOL'
    //         ) {
    //             $rightBlockIndex = $index;
    //             break;
    //         }
    //     }

    //     $leftBlockEnd =
    //         $rightBlockIndex
    //         ?? count($normalizedHeaders);

    //     $tlpgIndex = null;
    //     $eventColumns = [];
    //     $ignoredHeaders = [];

    //     for (
    //         $index = $nopolIndex + 1;
    //         $index < $leftBlockEnd;
    //         $index++
    //     ) {
    //         $header =
    //             $normalizedHeaders[$index] ?? '';

    //         if ($header === '') {
    //             continue;
    //         }

    //         if (
    //             $header === 'TLPG' ||
    //             str_contains($header, 'TERMINAL') ||
    //             str_starts_with($header, 'TLPG ')
    //         ) {
    //             $tlpgIndex = $index;
    //             continue;
    //         }

    //         $eventName =
    //             $this->canonicalEventHeader(
    //                 $header
    //             );

    //         if ($eventName) {
    //             $eventColumns[$index] = $eventName;
    //         } else {
    //             $ignoredHeaders[] = $header;
    //         }
    //     }

    //     if (empty($eventColumns)) {
    //         throw new RuntimeException(
    //             'Header jenis pelanggaran pada blok kiri tidak berhasil dikenali.'
    //         );
    //     }

    //     return [
    //         'month_year_index' =>
    //             (int) $monthYearIndex,

    //         'day_index' =>
    //             (int) $dayIndex,

    //         'nopol_index' =>
    //             (int) $nopolIndex,

    //         'tlpg_index' =>
    //             $tlpgIndex !== null
    //                 ? (int) $tlpgIndex
    //                 : null,

    //         'right_block_index' =>
    //             $rightBlockIndex,

    //         'event_columns' =>
    //             $eventColumns,

    //         'ignored_headers' =>
    //             $ignoredHeaders,
    //     ];
    // }

    private function leftBlockColumnConfig(): array
    {
        /*
        * Index array dimulai dari 0:
        *
        * 0  = A = BULAN DAN TAHUN
        * 1  = B = TANGGAL
        * 2  = C = NOPOL
        * 3  = D = TLPG
        * 4  = E dan seterusnya = jenis pelanggaran
        */
        return [
            'month_year_index' => 0,
            'day_index' => 1,
            'nopol_index' => 2,
            'tlpg_index' => 3,

            'event_columns' => [
                4 => 'Menerima Penumpang Selain AMT',

                5 => 'Mengemudi Lebih Dari 4 Jam',

                6 => 'Over Speed',

                7 => 'Perlambatan Mendadak',

                8 => 'Akselerasi Mendadak',

                9 => 'Tikungan Tajam',

                10 => 'Melebihi Batas Waktu Parkir',

                11 => 'Seat Belt',

                12 => 'Keluar Rute',

                13 => 'Berganti AMT Yang Tidak Berlisensi',

                14 => 'Menggunakan Handphone / Gadget',

                15 => 'Merokok / Vape',

                16 => 'Menutup / Mengubah Posisi CAM',

                17 => 'Merusak / Melepas Device GPS / CAM',

                18 => 'Pengurangan Bahan Bakar',

                19 => 'Berganti AMT Yang Tidak Berlisensi',

                20 => 'Pengemudi Kelelahan',

                21 => 'Mengemudi Tidak Baik Napza / Alkohol',

                22 => 'Menghilangkan Sinyal GPS / Jammer',

                23 => 'Geolokasi Blackzone / Redzone',

                24 => 'Pelecehan Verbal',

                25 => 'Mengintervensi / Mengancam Petugas RTC',
            ],
        ];
    }

    private function parseSourceDate(
        ?string $monthYear,
        mixed $day
    ): ?Carbon {
        $monthYear = trim(
            (string) $monthYear
        );

        $dayNumber = (int) preg_replace(
            '/[^0-9]/',
            '',
            (string) $day
        );

        if (
            $monthYear === '' ||
            $dayNumber < 1 ||
            $dayNumber > 31
        ) {
            return null;
        }

        $monthYear = Str::ascii(
            $monthYear
        );

        $monthTranslations = [
            'Januari' => 'January',
            'Jan' => 'January',

            'Februari' => 'February',
            'Pebruari' => 'February',
            'Feb' => 'February',

            'Maret' => 'March',
            'Mar' => 'March',

            'April' => 'April',
            'Apr' => 'April',

            'Mei' => 'May',
            'May' => 'May',

            'Juni' => 'June',
            'Jun' => 'June',

            'Juli' => 'July',
            'Jul' => 'July',

            'Agustus' => 'August',
            'Agu' => 'August',
            'Agt' => 'August',
            'Aug' => 'August',

            'September' => 'September',
            'Sep' => 'September',

            'Oktober' => 'October',
            'Okt' => 'October',
            'Oct' => 'October',

            'November' => 'November',
            'Nov' => 'November',

            'Desember' => 'December',
            'Des' => 'December',
            'Dec' => 'December',
        ];

        foreach (
            $monthTranslations
            as $indonesian => $english
        ) {
            $monthYear = preg_replace(
                '/\b' .
                preg_quote($indonesian, '/') .
                '\b/i',
                $english,
                $monthYear
            );
        }

        try {
            return Carbon::parse(
                $dayNumber . ' ' . $monthYear
            )->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseCount(
        mixed $value
    ): int {
        if ($value === null) {
            return 0;
        }

        $value = trim(
            (string) $value
        );

        if (
            $value === '' ||
            $value === '-' ||
            str_starts_with($value, '#')
        ) {
            return 0;
        }

        if (
            !preg_match(
                '/-?\d+(?:[.,]\d+)?/',
                $value,
                $match
            )
        ) {
            return 0;
        }

        $numeric = str_replace(
            ',',
            '.',
            $match[0]
        );

        return max(
            0,
            (int) round((float) $numeric)
        );
    }

    public function normalizeNopol(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        $value = strtoupper(
            Str::ascii(trim($value))
        );

        $value = preg_replace(
            '/[^A-Z0-9]+/',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    public function normalizeEventName(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        $normalized =
            $this->normalizeHeader($value);

        $known =
            $this->canonicalEventHeader(
                $normalized
            );

        if ($known) {
            return $known;
        }

        return Str::title(
            Str::lower($normalized)
        );
    }

    private function normalizeTlpg(
        mixed $value
    ): ?string {
        $value = trim(
            (string) $value
        );

        if (
            $value === '' ||
            str_starts_with($value, '#')
        ) {
            return null;
        }

        $value = strtoupper(
            Str::ascii($value)
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        return trim($value) ?: null;
    }

    private function normalizeHeader(
        mixed $value
    ): string {
        $value = strtoupper(
            Str::ascii(
                trim((string) $value)
            )
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

    /**
     * Header yang dikenali sebagai jenis pelanggaran.
     */
    private function canonicalEventHeader(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        $key = $this->normalizeHeader(
            $value
        );

        $aliases = [
            'MENERIMA PENUMPANG SELAIN AMT'
                => 'Menerima Penumpang Selain AMT',

            'MENGEMUDI LEBIH DARI 4 JAM'
                => 'Mengemudi Lebih Dari 4 Jam',

            'OVER SPEED'
                => 'Over Speed',

            'OVERSPEED'
                => 'Over Speed',

            'PERLAMBATAN MENDADAK'
                => 'Perlambatan Mendadak',

            'AKSELERASI MENDADAK'
                => 'Akselerasi Mendadak',

            'TIKUNGAN TAJAM'
                => 'Tikungan Tajam',

            'MELEBIHI BATAS WAKTU PARKIR'
                => 'Melebihi Batas Waktu Parkir',

            'SEAT BELT'
                => 'Seat Belt',

            'SEATBELT'
                => 'Seat Belt',

            'KELUAR RUTE'
                => 'Keluar Rute',

            'BERGANTI AMT TANPA LISENSI'
                => 'Berganti AMT Yang Tidak Berlisensi',

            'BERGANTI AMT YANG TIDAK BERLISENSI'
                => 'Berganti AMT Yang Tidak Berlisensi',

            'BERGANTI AMT YANG TIDAK BERLISENSI ACCIDENT'
                => 'Berganti AMT Yang Tidak Berlisensi',

            'MENGGUNAKAN HANDPHONE GADGET'
                => 'Menggunakan Handphone / Gadget',

            'MENGGUNAKAN HANDPHONE'
                => 'Menggunakan Handphone / Gadget',

            'MEROKOK VAPE'
                => 'Merokok / Vape',

            'MENUTUP MENGUBAH POSISI CAM'
                => 'Menutup / Mengubah Posisi CAM',

            'MENUTUP CAM'
                => 'Menutup / Mengubah Posisi CAM',

            'MENGUBAH POSISI CAM'
                => 'Menutup / Mengubah Posisi CAM',

            'MERUSAK MELEPAS DEVICE GPS CAM'
                => 'Merusak / Melepas Device GPS / CAM',

            'PENGURANGAN BAHAN BAKAR'
                => 'Pengurangan Bahan Bakar',

            'PENGEMUDI KELELAHAN ACCIDENT'
                => 'Pengemudi Kelelahan',

            'PENGEMUDI KELELAHAN'
                => 'Pengemudi Kelelahan',

            'MENGEMUDI TIDAK BAIK NAPZA ALKOHOL'
                => 'Mengemudi Tidak Baik Napza / Alkohol',

            'MENGHILANGKAN SINYAL GPS JAMMER'
                => 'Menghilangkan Sinyal GPS / Jammer',

            'GEOLOKASI BLACKZONE REDZONE'
                => 'Geolokasi Blackzone / Redzone',

            'PELECEHAN VERBAL'
                => 'Pelecehan Verbal',

            'MENGINTERVENSI MENGANCAM BEKERJA SAMA DENGAN PETUGAS RTC'
                => 'Mengintervensi / Mengancam Petugas RTC',
        ];

        return $aliases[$key] ?? null;
    }

    /**
 * Membaca ulang PDF pelanggaran lama dan mengisi event_time.
 *
 * Hanya memproses record pada rentang data PDF aktif.
 * Hanya record yang event_time-nya masih kosong.
 */
private function syncPdfEventTimes(
    Carbon $pdfStart,
    Carbon $pdfEnd
): array {
    $stats = [
        'files_checked' => 0,
        'files_updated' => 0,
        'events_updated' => 0,
        'time_not_found' => 0,
        'file_missing' => 0,
        'read_failed' => 0,
        'already_has_time' => 0,
    ];

    /*
     * Hitung record yang sebelumnya sudah memiliki jam.
     */
    $stats['already_has_time'] = MonitoringEvent::query()
        ->where('event_type', 'pelanggaran')
        ->whereBetween('event_date', [
            $pdfStart->toDateString(),
            $pdfEnd->toDateString(),
        ])
        ->whereNotNull('event_time')
        ->where('event_time', '!=', '')
        ->count();

    /*
     * Ambil event yang jamnya masih kosong.
     */
    $events = MonitoringEvent::query()
        ->with([
            'reportUpload:id,nama_file,path_file',
        ])
        ->where('event_type', 'pelanggaran')
        ->whereBetween('event_date', [
            $pdfStart->toDateString(),
            $pdfEnd->toDateString(),
        ])
        ->whereNotNull('report_upload_id')
        ->where(function ($query) {
            $query
                ->whereNull('event_time')
                ->orWhere('event_time', '');
        })
        ->orderBy('report_upload_id')
        ->get();

    /*
     * Satu file PDF bisa berhubungan dengan lebih dari satu event.
     * Agar file tidak dibaca berulang kali, kelompokkan berdasarkan upload.
     */
    $eventGroups = $events->groupBy(
        'report_upload_id'
    );

    foreach ($eventGroups as $reportUploadId => $group) {
        $stats['files_checked']++;

        /** @var MonitoringEvent|null $firstEvent */
        $firstEvent = $group->first();

        $upload = $firstEvent?->reportUpload;

        if (!$upload) {
            $stats['file_missing']++;
            continue;
        }

        $filePath = $this->resolveReportUploadPdfPath(
            $upload
        );

        if (!$filePath || !is_file($filePath)) {
            $stats['file_missing']++;
            continue;
        }

        $pdfText = $this->extractPdfText(
            $filePath
        );

        if (!$pdfText) {
            $stats['read_failed']++;
            continue;
        }

        /*
         * Jam wajib diambil dari baris Waktu Kejadian.
         * Jangan mengambil Waktu Pelaporan atau jam di kronologi.
         */
        $eventTime = $this->extractWaktuKejadianFromText(
            $pdfText
        );

        if (!$eventTime) {
            $stats['time_not_found']++;
            continue;
        }

        /*
         * Simpan sebagai format TIME database: HH:MM:SS.
         */
        $eventTimeDatabase = $eventTime . ':00';

        $updatedCount = MonitoringEvent::query()
            ->whereIn(
                'id',
                $group->pluck('id')->all()
            )
            ->where(function ($query) {
                $query
                    ->whereNull('event_time')
                    ->orWhere('event_time', '');
            })
            ->update([
                'event_time' => $eventTimeDatabase,
                'updated_at' => now(),
            ]);

        if ($updatedCount > 0) {
            $stats['files_updated']++;
            $stats['events_updated'] += $updatedCount;
        }
    }

    return $stats;
}

/**
 * Mengambil teks PDF menggunakan pdftotext.
 */
private function extractPdfText(
    string $filePath
): ?string {
    $commands = [
        'pdftotext',
        'pdftotext.exe',
    ];

    foreach ($commands as $command) {
        try {
            /*
             * -layout membantu mempertahankan posisi tabel.
             * Tanda "-" berarti hasil dikirim ke output, bukan file baru.
             */
            $process = new Process([
                $command,
                '-layout',
                $filePath,
                '-',
            ]);

            $process->setTimeout(120);
            $process->run();

            if (!$process->isSuccessful()) {
                continue;
            }

            $text = trim(
                $process->getOutput()
            );

            if (
                $text !== '' &&
                !str_starts_with($text, '%PDF')
            ) {
                return $text;
            }
        } catch (\Throwable $e) {
            /*
             * Coba nama command berikutnya.
             */
            continue;
        }
    }

    return null;
}

/**
 * Mengambil jam hanya dari bagian Waktu Kejadian.
 *
 * Contoh:
 * Waktu Kejadian ... Tanggal : 01 Juli 2026 ... Jam : 05:51 WIB
 *
 * Hasil:
 * 05:51
 */
private function extractWaktuKejadianFromText(
    ?string $text
): ?string {
    if (!$text) {
        return null;
    }

    /*
     * Rapikan karakter spasi tanpa menghilangkan seluruh struktur teks.
     */
    $text = str_replace(
        ["\r\n", "\r"],
        "\n",
        $text
    );

    $text = preg_replace(
        '/[ \t]+/u',
        ' ',
        $text
    );

    /*
     * Pola utama:
     * dimulai dari Waktu Kejadian,
     * berhenti sebelum Waktu Pelaporan,
     * lalu mengambil nilai setelah kata Jam.
     */
    if (
        preg_match(
            '/Waktu\s*Kejadian\b' .
            '(?:(?!Waktu\s*Pelaporan).){0,500}?' .
            '\bJam\s*[:\-]?\s*' .
            '([01]?\d|2[0-3])' .
            '[.:]' .
            '([0-5]\d)' .
            '(?:[.:]([0-5]\d))?' .
            '\s*(?:WIB|WITA|WIT)?/isu',
            $text,
            $match
        )
    ) {
        return sprintf(
            '%02d:%02d',
            (int) $match[1],
            (int) $match[2]
        );
    }

    /*
     * Fallback apabila hasil pdftotext menaruh Jam
     * di baris berbeda, tetapi masih dalam blok Waktu Kejadian.
     */
    if (
        preg_match(
            '/Waktu\s*Kejadian\b' .
            '(?:(?!Waktu\s*Pelaporan).){0,800}?' .
            '([01]?\d|2[0-3])' .
            '[.:]' .
            '([0-5]\d)' .
            '\s*(?:WIB|WITA|WIT)/isu',
            $text,
            $match
        )
    ) {
        return sprintf(
            '%02d:%02d',
            (int) $match[1],
            (int) $match[2]
        );
    }

    return null;
}

/**
 * Mencari lokasi file PDF yang tersimpan.
 */
private function resolveReportUploadPdfPath(
    ?ReportUpload $upload
): ?string {
    if (!$upload || !$upload->path_file) {
        return null;
    }

    $path = $upload->path_file;

    /*
     * Jika path_file sudah berupa path absolut.
     */
    if (is_file($path)) {
        return $path;
    }

    /*
     * Storage disk default.
     */
    if (Storage::exists($path)) {
        return Storage::path($path);
    }

    /*
     * storage/app/...
     */
    $candidate1 = storage_path(
        'app/' . ltrim($path, '/\\')
    );

    if (is_file($candidate1)) {
        return $candidate1;
    }

    /*
     * storage/app/public/...
     */
    $candidate2 = storage_path(
        'app/public/' . ltrim($path, '/\\')
    );

    if (is_file($candidate2)) {
        return $candidate2;
    }

    /*
     * public/storage/...
     */
    $candidate3 = public_path(
        'storage/' . ltrim($path, '/\\')
    );

    if (is_file($candidate3)) {
        return $candidate3;
    }

    return null;
}
}