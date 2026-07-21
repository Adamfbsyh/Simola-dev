<?php

namespace App\Services;

use App\Models\DriverDailyAssignment;
use App\Models\MonitoringEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DriverScoreService
{
    /**
     * Membuat ringkasan skor berdasarkan nama AMT.
     */
    public function summarize(
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?string $search = null
    ): Collection {
        $start = Carbon::parse($startDate)
            ->startOfDay();

        $end = Carbon::parse($endDate)
            ->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Master nama AMT K3-06.1
        |--------------------------------------------------------------------------
        */

        $masterNames = $this->buildMasterNameMap();

        /*
        |--------------------------------------------------------------------------
        | Aktivitas AMT dari K3-06.1
        |--------------------------------------------------------------------------
        */

        $activities = DriverDailyAssignment::query()
            ->whereBetween('source_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->whereNotNull('driver_name')
            ->where('driver_name', '!=', '')
            ->get([
                'source_date',
                'driver_name',
                'total_distance',
                'travel_seconds',
                'stop_seconds',
            ])
            ->map(function (DriverDailyAssignment $row) use (
                $masterNames
            ) {
                $key = $this->normalizeNameKey(
                    $row->driver_name
                );

                return [
                    'driver_key' => $key,

                    'driver_name' =>
                        $masterNames[$key]
                        ?? $this->normalizeDisplayName(
                            $row->driver_name
                        ),

                    'total_distance' => (float) (
                        $row->total_distance
                        ?? 0
                    ),

                    'travel_seconds' => (int) (
                        $row->travel_seconds
                        ?? 0
                    ),

                    'stop_seconds' => (int) (
                        $row->stop_seconds
                        ?? 0
                    ),

                    'activity_date' =>
                        $row->source_date
                            ? Carbon::parse(
                                $row->source_date
                            )->toDateString()
                            : null,
                ];
            })
            ->groupBy('driver_key')
            ->map(function (Collection $items) {
                return [
                    'driver_name' =>
                        $items->first()['driver_name'],

                    'total_distance' =>
                        round(
                            $items->sum('total_distance'),
                            2
                        ),

                    'travel_seconds' =>
                        $items->sum('travel_seconds'),

                    'stop_seconds' =>
                        $items->sum('stop_seconds'),

                    'active_days' =>
                        $items
                            ->pluck('activity_date')
                            ->filter()
                            ->unique()
                            ->count(),
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Kejadian dari form monitoring
        |--------------------------------------------------------------------------
        */

        $events = MonitoringEvent::query()
            ->whereBetween('event_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->whereIn('event_type', [
                'pelanggaran',
                'kendala',
                'accident',
            ])
            ->get([
                'id',
                'event_date',
                'event_type',
                'nopol',
                'driver_name',
                'score_impact',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tentukan nama resmi setiap kejadian
        |--------------------------------------------------------------------------
        */

        $resolvedEvents = $events->map(function (
            MonitoringEvent $event
        ) use (
            $masterNames
        ) {
            $rawDriverName = trim(
                (string) $event->driver_name
            );

            if ($this->isInvalidDriverName($rawDriverName)) {
                $driverKey =
                    '__UNIDENTIFIED__';

                $driverName =
                    'AMT BELUM TERIDENTIFIKASI';

                $registered =
                    false;

                $nameSource =
                    'unidentified';
            } else {
                $driverKey =
                    $this->normalizeNameKey(
                        $rawDriverName
                    );

                /*
                 * Jika nama ditemukan di K3-06.1,
                 * gunakan penulisan resmi dari K3-06.1.
                 */
                if (
                    $driverKey !== ''
                    &&
                    isset($masterNames[$driverKey])
                ) {
                    $driverName =
                        $masterNames[$driverKey];

                    $registered =
                        true;

                    $nameSource =
                        'k3061';
                } else {
                    /*
                     * Nama form tetap dipakai.
                     * Sistem tidak menebak nama yang mirip agar
                     * skor tidak masuk ke AMT yang salah.
                     */
                    $driverName =
                        $this->normalizeDisplayName(
                            $rawDriverName
                        );

                    $registered =
                        false;

                    $nameSource =
                        'form';
                }
            }

            return [
                'event_id' =>
                    $event->id,

                'event_date' =>
                    $event->event_date
                        ? Carbon::parse(
                            $event->event_date
                        )->toDateString()
                        : null,

                'event_type' =>
                    mb_strtolower(
                        trim(
                            (string) $event->event_type
                        ),
                        'UTF-8'
                    ),

                'driver_key' =>
                    $driverKey,

                'driver_name' =>
                    $driverName,

                'registered' =>
                    $registered,

                'name_source' =>
                    $nameSource,

                'nopol' =>
                    $this->normalizeNopol(
                        $event->nopol
                    ),

                'score' =>
                    (int) (
                        $event->score_impact
                        ?? 0
                    ),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Kelompokkan berdasarkan AMT
        |--------------------------------------------------------------------------
        */

        $summary = $resolvedEvents
            ->groupBy('driver_key')
            ->map(function (
                Collection $items,
                string $driverKey
            ) use (
                $activities
            ) {
                $first = $items->first();

                $nopols = $items
                    ->pluck('nopol')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                $activity = $activities->get(
                    $driverKey,
                    [
                        'total_distance' => 0,
                        'travel_seconds' => 0,
                        'stop_seconds' => 0,
                        'active_days' => 0,
                    ]
                );

                $pelanggaranCount = $items
                    ->where(
                        'event_type',
                        'pelanggaran'
                    )
                    ->count();

                $kendalaCount = $items
                    ->where(
                        'event_type',
                        'kendala'
                    )
                    ->count();

                $accidentCount = $items
                    ->where(
                        'event_type',
                        'accident'
                    )
                    ->count();

                return (object) [
                    'driver_key' =>
                        $driverKey,

                    'driver_name' =>
                        $first['driver_name'],

                    'registered_in_k3061' =>
                        $items
                            ->where(
                                'registered',
                                true
                            )
                            ->isNotEmpty(),

                    'name_source' =>
                        $first['name_source'],

                    'nopols' =>
                        $nopols->all(),

                    'nopol' =>
                        $nopols->implode(', '),

                    'total_nopol' =>
                        $nopols->count(),

                    'total_event' =>
                        $items->count(),

                    'total_risiko' =>
                        $items->sum('score'),

                    'total_pelanggaran' =>
                        $pelanggaranCount,

                    'total_kendala' =>
                        $kendalaCount,

                    'total_accident' =>
                        $accidentCount,

                    'total_distance' =>
                        (float) (
                            $activity['total_distance']
                            ?? 0
                        ),

                    'travel_seconds' =>
                        (int) (
                            $activity['travel_seconds']
                            ?? 0
                        ),

                    'stop_seconds' =>
                        (int) (
                            $activity['stop_seconds']
                            ?? 0
                        ),

                    'active_days' =>
                        (int) (
                            $activity['active_days']
                            ?? 0
                        ),
                ];
            })
            ->sortByDesc('total_risiko')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Pencarian nama AMT atau NOPOL
        |--------------------------------------------------------------------------
        */

        if (
            $search !== null
            &&
            trim($search) !== ''
        ) {
            $searchValue = $this->normalizeSearch(
                $search
            );

            $summary = $summary
                ->filter(function ($row) use (
                    $searchValue
                ) {
                    $driverName =
                        $this->normalizeSearch(
                            $row->driver_name
                        );

                    $nopol =
                        $this->normalizeSearch(
                            $row->nopol
                        );

                    return str_contains(
                        $driverName,
                        $searchValue
                    )
                    ||
                    str_contains(
                        $nopol,
                        $searchValue
                    );
                })
                ->values();
        }

        return $summary;
    }

    /**
     * Daftar penulisan resmi nama AMT dari K3-06.1.
     */
    private function buildMasterNameMap(): array
    {
        return DriverDailyAssignment::query()
            ->whereNotNull('driver_name')
            ->where('driver_name', '!=', '')
            ->orderByDesc('source_date')
            ->orderByDesc('id')
            ->get([
                'driver_name',
                'source_date',
            ])
            ->groupBy(function (
                DriverDailyAssignment $row
            ) {
                return $this->normalizeNameKey(
                    $row->driver_name
                );
            })
            ->filter(function (
                Collection $items,
                string $key
            ) {
                return $key !== '';
            })
            ->map(function (
                Collection $items
            ) {
                /*
                 * Ambil penulisan terbaru dari K3-06.1.
                 */
                return $this->normalizeDisplayName(
                    $items->first()->driver_name
                );
            })
            ->all();
    }

    /**
     * Normalisasi key agar tanda titik, koma, dan spasi
     * tidak membuat satu AMT menjadi beberapa kelompok.
     *
     * Contoh:
     * ACH. MUKSIN = ACH MUKSIN
     */
    private function normalizeNameKey(
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

        $value = Str::ascii(
            $value
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

    private function normalizeDisplayName(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

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

    private function normalizeNopol(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return '';
        }

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

    private function normalizeSearch(
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

    private function isInvalidDriverName(
        mixed $value
    ): bool {
        $key = $this->normalizeNameKey(
            $value
        );

        if ($key === '') {
            return true;
        }

        /*
        * Header, label form, dan nilai bukan nama AMT.
        */
        $invalidNames = [
            'MT',
            'MT NOPOL',
            'MT NO POL',
            'NOPOL',
            'NO POL',
            'NOMOR POLISI',

            'AMT',
            'DRIVER',
            'PENGEMUDI',

            'NAMA',
            'NAMA AMT',
            'NAMA DRIVER',
            'NAMA PENGEMUDI',

            'TLPG',
            'TERMINAL',
            'TERMINALS',

            'TOTAL',
            'JUMLAH',
            'NO',
        ];

        if (
            in_array(
                $key,
                $invalidNames,
                true
            )
        ) {
            return true;
        }

        if (
            str_starts_with($key, 'MT NOPOL')
            ||
            str_starts_with($key, 'MT NO POL')
            ||
            str_starts_with($key, 'NAMA AMT')
        ) {
            return true;
        }

        return false;
    }
}