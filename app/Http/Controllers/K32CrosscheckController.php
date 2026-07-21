<?php

namespace App\Http\Controllers;

use App\Models\K32DailyRecord;
use App\Models\MonitoringEvent;
use App\Services\K32DailyService;
use App\Services\PelanggaranCategoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class K32CrosscheckController extends Controller
{
    public function index(
        Request $request,
        K32DailyService $service,
        PelanggaranCategoryService $categoryService
    ) {
        $pdfStartValue = MonitoringEvent::query()
            ->where('event_type', 'pelanggaran')
            ->whereNotNull('event_date')
            ->min('event_date');

        $pdfEndValue = MonitoringEvent::query()
            ->where('event_type', 'pelanggaran')
            ->whereNotNull('event_date')
            ->max('event_date');

        $pdfStart = $pdfStartValue
            ? Carbon::parse($pdfStartValue)
            : null;

        $pdfEnd = $pdfEndValue
            ? Carbon::parse($pdfEndValue)
            : null;

        $allResults = ($pdfStart && $pdfEnd)
            ? $this->buildResults(
                $pdfStart,
                $pdfEnd,
                $service,
                $categoryService
            )
            : collect();

        /*
         * Filter selalu memakai 22 nama resmi Form K3.2.
         */
        $eventOptions = collect(
            $categoryService->master()
        );

        $filtered = $allResults;

        /*
        |--------------------------------------------------------------------------
        | Filter tanggal
        |--------------------------------------------------------------------------
        */
        if ($request->filled('tanggal')) {
            $filtered = $filtered->where(
                'date',
                (string) $request->tanggal
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter bulan
        |--------------------------------------------------------------------------
        */
        if ($request->filled('bulan')) {
            $bulan = (string) $request->bulan;

            $filtered = $filtered->filter(
                fn (array $row) =>
                    str_starts_with(
                        (string) $row['date'],
                        $bulan
                    )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pencarian
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = mb_strtolower(
                trim((string) $request->search),
                'UTF-8'
            );

            $filtered = $filtered->filter(
                function (array $row) use ($search) {
                    foreach (
                        [
                            'nopol',
                            'event_name',
                            'tlpg_k32',
                            'tlpg_pdf',
                        ]
                        as $field
                    ) {
                        $value = mb_strtolower(
                            (string) (
                                $row[$field] ?? ''
                            ),
                            'UTF-8'
                        );

                        if (
                            str_contains(
                                $value,
                                $search
                            )
                        ) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter jenis pelanggaran
        |--------------------------------------------------------------------------
        */
        if ($request->filled('event_name')) {
            $selectedEvent =
                $categoryService->canonicalize(
                    (string) $request->event_name
                )
                ?: trim(
                    (string) $request->event_name
                );

            $filtered = $filtered->where(
                'event_name',
                $selectedEvent
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter status
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $filtered = $filtered->where(
                'status_code',
                (string) $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pengurutan
        |--------------------------------------------------------------------------
        */
        $filtered = $filtered
            ->sort(
                function (
                    array $left,
                    array $right
                ) {
                    if (
                        $left['date']
                        !== $right['date']
                    ) {
                        return strcmp(
                            (string) $right['date'],
                            (string) $left['date']
                        );
                    }

                    $nopolCompare = strcmp(
                        (string) $left['nopol'],
                        (string) $right['nopol']
                    );

                    if ($nopolCompare !== 0) {
                        return $nopolCompare;
                    }

                    return strcmp(
                        (string) $left['event_name'],
                        (string) $right['event_name']
                    );
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Ringkasan
        |--------------------------------------------------------------------------
        */
        $summary = [
            'total_k32' =>
                $filtered->sum('k32_count'),

            'total_pdf_raw' =>
                $filtered->sum('pdf_raw_count'),

            'total_pdf_unique' =>
                $filtered->sum('pdf_unique_count'),

            'total_duplicate' =>
                $filtered->sum('pdf_duplicate_count'),

            'total_time_unreadable' =>
                $filtered->sum(
                    'time_unreadable_count'
                ),

            'sesuai' =>
                $filtered
                    ->where(
                        'status_code',
                        'sesuai'
                    )
                    ->count(),

            'sesuai_ada_duplikat' =>
                $filtered
                    ->where(
                        'status_code',
                        'sesuai_ada_duplikat'
                    )
                    ->count(),

            'duplikat_pdf' =>
                $filtered
                    ->where(
                        'status_code',
                        'duplikat_pdf'
                    )
                    ->count(),

            'jam_tidak_terbaca' =>
                $filtered
                    ->where(
                        'status_code',
                        'jam_tidak_terbaca'
                    )
                    ->count(),

            'kurang_pdf' =>
                $filtered
                    ->where(
                        'status_code',
                        'kurang_pdf'
                    )
                    ->count(),

            'lebih_pdf' =>
                $filtered
                    ->where(
                        'status_code',
                        'lebih_pdf'
                    )
                    ->count(),

            'hanya_k32' =>
                $filtered
                    ->where(
                        'status_code',
                        'hanya_k32'
                    )
                    ->count(),

            'hanya_pdf' =>
                $filtered
                    ->where(
                        'status_code',
                        'hanya_pdf'
                    )
                    ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $page =
            LengthAwarePaginator::resolveCurrentPage();

        $perPage = 25;

        $results = new LengthAwarePaginator(
            $filtered
                ->forPage(
                    $page,
                    $perPage
                )
                ->values(),

            $filtered->count(),

            $perPage,

            $page,

            [
                'path' =>
                    $request->url(),

                'query' =>
                    $request->query(),
            ]
        );

        $lastSyncedAt =
            K32DailyRecord::query()
                ->max('synced_at');

        $totalStored =
            K32DailyRecord::query()
                ->count();

        $statusOptions = [
            'sesuai' =>
                'Sesuai',

            'sesuai_ada_duplikat' =>
                'Sesuai, Ada Duplikat',

            'duplikat_pdf' =>
                'Kemungkinan Duplikat PDF',

            'jam_tidak_terbaca' =>
                'Jam Belum Terbaca',

            'kurang_pdf' =>
                'Kurang di PDF',

            'lebih_pdf' =>
                'Lebih di PDF',

            'hanya_k32' =>
                'Hanya di K3.2',

            'hanya_pdf' =>
                'Hanya di PDF',
        ];

        return view(
            'k32-crosscheck.index',
            compact(
                'results',
                'summary',
                'pdfStart',
                'pdfEnd',
                'eventOptions',
                'statusOptions',
                'lastSyncedAt',
                'totalStored'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi manual
    |--------------------------------------------------------------------------
    */
    public function sync(
        K32DailyService $service
    ) {
        try {
            $stats =
                $service->syncFromGoogleSheet();

            $message =
                'Sinkronisasi selesai. ' .

                'Data K3.2: ' .
                ($stats['records_saved'] ?? 0) .
                ' kombinasi dengan total ' .
                ($stats['total_value'] ?? 0) .
                ' kejadian. ' .

                'PDF diperiksa: ' .
                ($stats['pdf_files_checked'] ?? 0) .
                ' file. ' .

                'Jam berhasil diperbarui: ' .
                ($stats['pdf_events_updated'] ?? 0) .
                ' data dari ' .
                ($stats['pdf_files_updated'] ?? 0) .
                ' file. ' .

                'Jam tidak ditemukan: ' .
                ($stats['pdf_time_not_found'] ?? 0) .
                ' file. ' .

                'File tidak ditemukan: ' .
                ($stats['pdf_file_missing'] ?? 0) .
                '. ' .

                'PDF gagal dibaca: ' .
                ($stats['pdf_read_failed'] ?? 0) .
                '.';

            return redirect()
                ->route('k32.index')
                ->with(
                    'success',
                    $message
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('k32.index')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Membentuk hasil crosscheck
    |--------------------------------------------------------------------------
    */
    private function buildResults(
        Carbon $pdfStart,
        Carbon $pdfEnd,
        K32DailyService $service,
        PelanggaranCategoryService $categoryService
    ): Collection {
        /*
        |--------------------------------------------------------------------------
        | Data Form K3.2
        |--------------------------------------------------------------------------
        */
        $k32Map = [];

        $k32Records =
            K32DailyRecord::query()
                ->whereBetween(
                    'source_date',
                    [
                        $pdfStart
                            ->toDateString(),

                        $pdfEnd
                            ->toDateString(),
                    ]
                )
                ->get();

        foreach ($k32Records as $record) {
            $date = Carbon::parse(
                $record->source_date
            )->format('Y-m-d');

            $nopol =
                $service->normalizeNopol(
                    $record->nopol
                );

            $rawEventName =
                $service->normalizeEventName(
                    $record->event_name
                );

            /*
             * Nama K3.2 juga dinormalisasi agar hasil akhirnya
             * selalu mengikuti 22 nama resmi.
             */
            $eventName =
                $categoryService->canonicalize(
                    $rawEventName
                );

            if (
                !$nopol ||
                !$eventName
            ) {
                continue;
            }

            $key = $this->buildKey(
                $date,
                $nopol,
                $eventName
            );

            if (!isset($k32Map[$key])) {
                $k32Map[$key] = [
                    'date' =>
                        $date,

                    'nopol' =>
                        $nopol,

                    'event_name' =>
                        $eventName,

                    'tlpg' =>
                        $record->tlpg,

                    'count' =>
                        0,
                ];
            }

            $k32Map[$key]['count'] +=
                (int) $record->spreadsheet_count;

            if (
                !$k32Map[$key]['tlpg'] &&
                $record->tlpg
            ) {
                $k32Map[$key]['tlpg'] =
                    $record->tlpg;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Data PDF
        |--------------------------------------------------------------------------
        */
        $pdfMap = [];

        $pdfEvents =
            MonitoringEvent::query()
                ->with(
                    'reportUpload:id,nama_file'
                )
                ->where(
                    'event_type',
                    'pelanggaran'
                )
                ->whereBetween(
                    'event_date',
                    [
                        $pdfStart
                            ->toDateString(),

                        $pdfEnd
                            ->toDateString(),
                    ]
                )
                ->whereNotNull(
                    'event_date'
                )
                ->whereNotNull(
                    'nopol'
                )
                ->whereNotNull(
                    'event_name'
                )
                ->get();

        foreach ($pdfEvents as $event) {
            $date = Carbon::parse(
                $event->event_date
            )->format('Y-m-d');

            $nopol =
                $service->normalizeNopol(
                    $event->nopol
                );

            $rawEventName =
                $service->normalizeEventName(
                    $event->event_name
                );

            /*
             * Beberapa sumber teks PDF digunakan agar
             * variasi kalimat lebih mudah dikenali.
             */
            $classificationText = implode(
                ' ',
                array_filter([
                    $rawEventName,
                    $event->category,
                    $event->description,
                    $event->reportUpload
                        ?->nama_file,
                ])
            );

            /*
             * Nama PDF diubah ke salah satu dari
             * 22 nama resmi K3.2.
             */
            $eventName =
                $categoryService->canonicalize(
                    $classificationText
                );

            if (
                !$nopol ||
                !$eventName
            ) {
                continue;
            }

            $key = $this->buildKey(
                $date,
                $nopol,
                $eventName
            );

            if (!isset($pdfMap[$key])) {
                $pdfMap[$key] = [
                    'date' =>
                        $date,

                    'nopol' =>
                        $nopol,

                    'event_name' =>
                        $eventName,

                    'tlpg' =>
                        $event->tlpg,

                    'raw_count' =>
                        0,

                    'time_groups' =>
                        [],

                    'unreadable_time_events' =>
                        [],

                    'files' =>
                        [],
                ];
            }

            $pdfMap[$key]['raw_count']++;

            if (
                !$pdfMap[$key]['tlpg'] &&
                $event->tlpg
            ) {
                $pdfMap[$key]['tlpg'] =
                    $event->tlpg;
            }

            $file = null;

            if ($event->reportUpload) {
                $file = [
                    'id' =>
                        $event
                            ->reportUpload
                            ->id,

                    'name' =>
                        $event
                            ->reportUpload
                            ->nama_file,

                    'shift' =>
                        $this->extractShift(
                            $event
                                ->reportUpload
                                ->nama_file
                        ),
                ];

                $pdfMap[$key]['files'][
                    $event->reportUpload->id
                ] = $file;
            }

            /*
             * Ambil jam kejadian.
             */
            $eventTime =
                $this->extractEventTime(
                    $event
                );

            if ($eventTime) {
                if (
                    !isset(
                        $pdfMap[$key]
                        ['time_groups']
                        [$eventTime]
                    )
                ) {
                    $pdfMap[$key]
                    ['time_groups']
                    [$eventTime] = [
                        'time' =>
                            $eventTime,

                        'count' =>
                            0,

                        'files' =>
                            [],

                        'shifts' =>
                            [],
                    ];
                }

                $pdfMap[$key]
                ['time_groups']
                [$eventTime]
                ['count']++;

                if ($file) {
                    $pdfMap[$key]
                    ['time_groups']
                    [$eventTime]
                    ['files']
                    [$file['id']] = $file;

                    if ($file['shift']) {
                        $pdfMap[$key]
                        ['time_groups']
                        [$eventTime]
                        ['shifts']
                        [$file['shift']] =
                            $file['shift'];
                    }
                }
            } else {
                $pdfMap[$key]
                ['unreadable_time_events'][] = [
                    'event_id' =>
                        $event->id,

                    'file' =>
                        $file,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Gabungkan seluruh key K3.2 dan PDF
        |--------------------------------------------------------------------------
        */
        $keys = array_values(
            array_unique(
                array_merge(
                    array_keys($k32Map),
                    array_keys($pdfMap)
                )
            )
        );

        $results = [];

        foreach ($keys as $key) {
            $k32 =
                $k32Map[$key]
                ?? null;

            $pdf =
                $pdfMap[$key]
                ?? null;

            $k32Count = (int) (
                $k32['count']
                ?? 0
            );

            $pdfRawCount = (int) (
                $pdf['raw_count']
                ?? 0
            );

            $timeGroups =
                $pdf['time_groups']
                ?? [];

            $unreadableTimeEvents =
                $pdf['unreadable_time_events']
                ?? [];

            $unreadableTimeCount =
                count(
                    $unreadableTimeEvents
                );

            /*
             * Satu jam berbeda dianggap satu kejadian unik.
             * PDF tanpa jam tetap dihitung sebagai satu record.
             */
            $pdfUniqueCount =
                count($timeGroups) +
                $unreadableTimeCount;

            /*
             * File tambahan pada jam yang sama dianggap
             * kemungkinan duplikat.
             */
            $pdfDuplicateCount =
                collect($timeGroups)
                    ->sum(
                        fn (array $group) =>
                            max(
                                (
                                    (int)
                                    $group['count']
                                ) - 1,
                                0
                            )
                    );

            $timeDetails = [];

            foreach ($timeGroups as $group) {
                $timeDetails[] = [
                    'time' =>
                        $group['time'],

                    'count' =>
                        (int)
                        $group['count'],

                    'duplicate' =>
                        (int)
                        $group['count'] > 1,

                    'shifts' =>
                        array_values(
                            $group['shifts']
                        ),

                    'files' =>
                        array_values(
                            $group['files']
                        ),
                ];
            }

            /*
             * Tambahkan PDF yang jamnya belum terbaca.
             */
            foreach (
                $unreadableTimeEvents
                as $unreadableEvent
            ) {
                $timeDetails[] = [
                    'time' =>
                        null,

                    'count' =>
                        1,

                    'duplicate' =>
                        false,

                    'shifts' =>
                        array_values(
                            array_filter([
                                $unreadableEvent
                                ['file']
                                ['shift']
                                ?? null,
                            ])
                        ),

                    'files' =>
                        array_values(
                            array_filter([
                                $unreadableEvent
                                ['file']
                                ?? null,
                            ])
                        ),
                ];
            }

            usort(
                $timeDetails,
                function (
                    array $left,
                    array $right
                ) {
                    if (
                        $left['time']
                        === null
                    ) {
                        return 1;
                    }

                    if (
                        $right['time']
                        === null
                    ) {
                        return -1;
                    }

                    return strcmp(
                        $left['time'],
                        $right['time']
                    );
                }
            );

            [
                $statusCode,
                $statusLabel,
            ] = $this->determineStatus(
                $k32Count,
                $pdfRawCount,
                $pdfUniqueCount,
                $pdfDuplicateCount,
                $unreadableTimeCount
            );

            $results[] = [
                'date' =>
                    $k32['date']
                    ?? $pdf['date'],

                'nopol' =>
                    $k32['nopol']
                    ?? $pdf['nopol'],

                /*
                 * Nama yang ditampilkan selalu nama resmi K3.2.
                 */
                'event_name' =>
                    $k32['event_name']
                    ?? $pdf['event_name'],

                'tlpg_k32' =>
                    $k32['tlpg']
                    ?? null,

                'tlpg_pdf' =>
                    $pdf['tlpg']
                    ?? null,

                'k32_count' =>
                    $k32Count,

                'pdf_count' =>
                    $pdfRawCount,

                'pdf_raw_count' =>
                    $pdfRawCount,

                'pdf_unique_count' =>
                    $pdfUniqueCount,

                'pdf_duplicate_count' =>
                    $pdfDuplicateCount,

                'time_unreadable_count' =>
                    $unreadableTimeCount,

                'difference' =>
                    $pdfUniqueCount -
                    $k32Count,

                'status_code' =>
                    $statusCode,

                'status_label' =>
                    $statusLabel,

                'files' =>
                    isset(
                        $pdf['files']
                    )
                        ? array_values(
                            $pdf['files']
                        )
                        : [],

                'time_details' =>
                    $timeDetails,
            ];
        }

        return collect($results);
    }

    /*
    |--------------------------------------------------------------------------
    | Penentuan status crosscheck
    |--------------------------------------------------------------------------
    */
    private function determineStatus(
        int $k32Count,
        int $pdfRawCount,
        int $pdfUniqueCount,
        int $pdfDuplicateCount,
        int $unreadableTimeCount
    ): array {
        if (
            $k32Count > 0 &&
            $pdfRawCount === 0
        ) {
            return [
                'hanya_k32',
                'Hanya di K3.2',
            ];
        }

        if (
            $pdfRawCount > 0 &&
            $k32Count === 0
        ) {
            return [
                'hanya_pdf',
                'Hanya di PDF',
            ];
        }

        /*
         * Apabila jam belum terbaca, jumlah file masih dapat
         * dibandingkan tetapi duplikat belum dapat dipastikan.
         */
        if ($unreadableTimeCount > 0) {
            if (
                $pdfRawCount ===
                $k32Count
            ) {
                return [
                    'jam_tidak_terbaca',
                    'Jumlah sesuai, jam belum lengkap',
                ];
            }

            if (
                $pdfRawCount <
                $k32Count
            ) {
                return [
                    'kurang_pdf',

                    'Kurang ' .
                    (
                        $k32Count -
                        $pdfRawCount
                    ) .
                    ' PDF, jam belum lengkap',
                ];
            }

            return [
                'lebih_pdf',

                'Lebih ' .
                (
                    $pdfRawCount -
                    $k32Count
                ) .
                ' PDF, jam belum lengkap',
            ];
        }

        /*
         * Semua jam berhasil terbaca.
         */
        if ($pdfDuplicateCount > 0) {
            if (
                $pdfUniqueCount ===
                $k32Count
            ) {
                return [
                    'sesuai_ada_duplikat',

                    'Sesuai, ada ' .
                    $pdfDuplicateCount .
                    ' kemungkinan duplikat',
                ];
            }

            return [
                'duplikat_pdf',
                'Kemungkinan duplikat PDF',
            ];
        }

        if (
            $pdfUniqueCount ===
            $k32Count
        ) {
            return [
                'sesuai',
                'Sesuai',
            ];
        }

        if (
            $pdfUniqueCount <
            $k32Count
        ) {
            return [
                'kurang_pdf',

                'Kurang ' .
                (
                    $k32Count -
                    $pdfUniqueCount
                ) .
                ' kejadian di PDF',
            ];
        }

        return [
            'lebih_pdf',

            'Lebih ' .
            (
                $pdfUniqueCount -
                $k32Count
            ) .
            ' kejadian di PDF',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Membuat key pencocokan
    |--------------------------------------------------------------------------
    */
    private function buildKey(
        string $date,
        string $nopol,
        string $eventName
    ): string {
        return implode(
            '|',
            [
                $date,
                $nopol,
                $eventName,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Membaca jam kejadian
    |--------------------------------------------------------------------------
    */
    private function extractEventTime(
        MonitoringEvent $event
    ): ?string {
        /*
         * Prioritas pertama: event_time.
         */
        $directTime =
            $this->normalizeEventTime(
                $event->event_time
            );

        if ($directTime) {
            return $directTime;
        }

        /*
         * Cari pada raw_data.
         */
        if ($event->raw_data) {
            $rawData =
                is_string(
                    $event->raw_data
                )
                    ? $event->raw_data
                    : json_encode(
                        $event->raw_data,
                        JSON_UNESCAPED_UNICODE
                    );

            $time =
                $this->normalizeEventTime(
                    $rawData
                );

            if ($time) {
                return $time;
            }
        }

        /*
         * Cari pada description.
         */
        if ($event->description) {
            $time =
                $this->normalizeEventTime(
                    $event->description
                );

            if ($time) {
                return $time;
            }
        }

        /*
         * Pilihan terakhir: nama file.
         */
        if (
            $event
                ->reportUpload
                ?->nama_file
        ) {
            return $this->normalizeEventTime(
                $event
                    ->reportUpload
                    ->nama_file
            );
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisasi format jam
    |--------------------------------------------------------------------------
    */
    private function normalizeEventTime(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            $value instanceof
            \DateTimeInterface
        ) {
            return $value->format(
                'H:i'
            );
        }

        $text = trim(
            (string) $value
        );

        if ($text === '') {
            return null;
        }

        /*
         * Format dengan label.
         *
         * Jam: 08:15
         * Waktu Kejadian 17.42
         * Pukul 23:10
         */
        if (
            preg_match(
                '/(?:JAM|WAKTU(?:\s+KEJADIAN)?|PUKUL)\s*[:\-]?\s*' .
                '([01]?\d|2[0-3])[.:]([0-5]\d)(?:[.:]([0-5]\d))?/i',

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
         * Format jam umum.
         */
        if (
            preg_match(
                '/\b([01]?\d|2[0-3])[.:]([0-5]\d)(?:[.:]([0-5]\d))?\b/',

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

    /*
    |--------------------------------------------------------------------------
    | Membaca shift dari nama file
    |--------------------------------------------------------------------------
    */
    private function extractShift(
        ?string $fileName
    ): ?string {
        if (!$fileName) {
            return null;
        }

        if (
            preg_match(
                '/\bSHIFT[\s_-]*([123])\b/i',

                $fileName,

                $match
            )
        ) {
            return 'Shift ' .
                $match[1];
        }

        return null;
    }
}