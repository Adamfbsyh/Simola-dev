<?php

namespace App\Http\Controllers;

use App\Models\MonitoringEvent;
use App\Models\ReportUpload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Str;
use App\Services\DriverScoreService;
use Illuminate\Pagination\LengthAwarePaginator;

class MonitoringDashboardController extends Controller
{
    public function __construct(
        private readonly DriverScoreService $driverScoreService
    ) {

    }

    public function index(Request $request)
    {
        $baseQuery = MonitoringEvent::query();

        $totalFile = ReportUpload::count();

        $totalPelanggaran = (clone $baseQuery)
            ->where('event_type', 'pelanggaran')
            ->count();

        $totalKendala = (clone $baseQuery)
            ->where('event_type', 'kendala')
            ->count();

        $totalAccident = (clone $baseQuery)
            ->where('event_type', 'accident')
            ->count();

        $totalErrorlog = (clone $baseQuery)
            ->where('event_type', 'errorlog')
            ->count();

        $totalNopol = (clone $baseQuery)
            ->whereNotNull('nopol')
            ->where('nopol', '!=', '')
            ->distinct()
            ->count('nopol');
        
        $totalPengemudi = (clone $baseQuery)
            ->whereIn('event_type', [
                'pelanggaran',
                'kendala',
                'accident',
            ])
            ->whereNotNull('driver_name')
            ->where('driver_name', '!=', '')
            ->distinct()
            ->count('driver_name');

        $latestDate = MonitoringEvent::whereNotNull('event_date')
            ->max('event_date');

        /*
        |--------------------------------------------------------------------------
        | Periode grafik
        |--------------------------------------------------------------------------
        */
        $trendPeriod = $request->get('trend_period', 'bulanan');

        if (!in_array(
            $trendPeriod,
            ['harian', 'mingguan', 'bulanan', 'tahunan'],
            true
        )) {
            $trendPeriod = 'bulanan';
        }

        /*
        |--------------------------------------------------------------------------
        | Jenis data yang ditampilkan
        |--------------------------------------------------------------------------
        */
        $seriesList = [
            'pelanggaran',
            'kendala',
            'accident',
            'errorlog',
        ];

        $visibleSeries = $request->get('series', $seriesList);

        if (!is_array($visibleSeries)) {
            $visibleSeries = $seriesList;
        }

        $visibleSeries = array_values(
            array_unique(
                array_intersect($visibleSeries, $seriesList)
            )
        );

        if (empty($visibleSeries)) {
            $visibleSeries = $seriesList;
        }

        /*
        |--------------------------------------------------------------------------
        | Filter rentang dan perbandingan bulan
        |--------------------------------------------------------------------------
        */
        $trendMonthStart = $request->get('trend_month_start');
        $trendMonthEnd = $request->get('trend_month_end');

        $compareCurrentMonth = $request->get('compare_current_month');
        $comparePreviousMonth = $request->get('compare_previous_month');

        /*
         * Jika hanya Bulan yang Dicek diisi tanpa bulan pembanding,
         * mode harian dan mingguan otomatis mengikuti bulan tersebut.
         */
        if (
            !$trendMonthStart &&
            !$trendMonthEnd &&
            $compareCurrentMonth &&
            !$comparePreviousMonth &&
            in_array($trendPeriod, ['harian', 'mingguan'], true)
        ) {
            $trendMonthStart = $compareCurrentMonth;
            $trendMonthEnd = $compareCurrentMonth;
        }

        /*
        |--------------------------------------------------------------------------
        | Kartu perbandingan
        |--------------------------------------------------------------------------
        */
        $customCompare = $this->getCustomComparePeriod(
            $compareCurrentMonth,
            $comparePreviousMonth
        );

        $periodInfo = $customCompare
            ?: $this->getPeriodInfo($trendPeriod, $latestDate);

        $comparisonTitle = 'Perbandingan ' . $periodInfo['label'];

        $comparisonSubtitle =
            'Membandingkan data ' .
            $periodInfo['current_label'] .
            ' dengan ' .
            $periodInfo['previous_label'] .
            '. Penurunan angka pada pelanggaran, kendala, accident, dan errorlog dianggap sebagai kondisi membaik.';

        $current = [
            'pelanggaran' => $this->countByDateRange(
                'pelanggaran',
                $periodInfo['current_start'],
                $periodInfo['current_end']
            ),

            'kendala' => $this->countByDateRange(
                'kendala',
                $periodInfo['current_start'],
                $periodInfo['current_end']
            ),

            'accident' => $this->countByDateRange(
                'accident',
                $periodInfo['current_start'],
                $periodInfo['current_end']
            ),

            'errorlog' => $this->countByDateRange(
                'errorlog',
                $periodInfo['current_start'],
                $periodInfo['current_end']
            ),
        ];

        $previous = [
            'pelanggaran' => $this->countByDateRange(
                'pelanggaran',
                $periodInfo['previous_start'],
                $periodInfo['previous_end']
            ),

            'kendala' => $this->countByDateRange(
                'kendala',
                $periodInfo['previous_start'],
                $periodInfo['previous_end']
            ),

            'accident' => $this->countByDateRange(
                'accident',
                $periodInfo['previous_start'],
                $periodInfo['previous_end']
            ),

            'errorlog' => $this->countByDateRange(
                'errorlog',
                $periodInfo['previous_start'],
                $periodInfo['previous_end']
            ),
        ];

        $labelMap = [
            'pelanggaran' => 'Pelanggaran',
            'kendala' => 'Kendala',
            'accident' => 'Accident',
            'errorlog' => 'Errorlog',
        ];

        $comparisonCards = [];

        foreach ($visibleSeries as $key) {
            $comparisonCards[$key] = [
                'label' => $labelMap[$key],
                ...$this->bandingkan(
                    $current[$key] ?? 0,
                    $previous[$key] ?? 0
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Judul grafik tren
        |--------------------------------------------------------------------------
        */
        $trendTitle = 'Tren Monitoring ' . ucfirst($trendPeriod);

        $trendSubtitle =
            'Menampilkan perkembangan Pelanggaran, Kendala, Accident, dan Errorlog berdasarkan periode ' .
            $trendPeriod .
            '.';

        /*
        |--------------------------------------------------------------------------
        | Grafik perbandingan dua bulan
        |--------------------------------------------------------------------------
        */
        $comparisonTrendMode = false;
        $comparisonTrendData = null;

        if (
            $compareCurrentMonth &&
            $comparePreviousMonth &&
            in_array(
                $trendPeriod,
                ['harian', 'mingguan', 'bulanan'],
                true
            )
        ) {
            $comparisonTrendData = $this->buildComparisonTrendData(
                $trendPeriod,
                $compareCurrentMonth,
                $comparePreviousMonth,
                $visibleSeries
            );

            $comparisonTrendMode = $comparisonTrendData !== null;

            if ($comparisonTrendMode) {
                $currentLabel = $this->formatMonthInputLabel(
                    $compareCurrentMonth
                );

                $previousLabel = $this->formatMonthInputLabel(
                    $comparePreviousMonth
                );

                $trendTitle = 'Tren Perbandingan ' . ucfirst($trendPeriod);

                $trendSubtitle =
                    'Membandingkan ' .
                    $currentLabel .
                    ' dengan ' .
                    $previousLabel .
                    ' untuk data yang dipilih.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Data grafik tren normal
        |--------------------------------------------------------------------------
        */
        $trenBulanan = $this->buildTrendData(
            $trendPeriod,
            $latestDate,
            $trendMonthStart,
            $trendMonthEnd
        );

        /*
        |--------------------------------------------------------------------------
        | Bulan untuk grafik kategori bawah
        |--------------------------------------------------------------------------
        */
        $latestChartDate = MonitoringEvent::whereNotNull('event_date')
            ->max('event_date');

        $categoryChartMonthInput = $compareCurrentMonth;

        try {
            if ($categoryChartMonthInput) {
                $categoryChartMonth = Carbon::createFromFormat(
                    'Y-m',
                    $categoryChartMonthInput
                )->startOfMonth();
            } elseif ($latestChartDate) {
                $categoryChartMonth = Carbon::parse(
                    $latestChartDate
                )->startOfMonth();
            } else {
                $categoryChartMonth = now()->startOfMonth();
            }
        } catch (\Throwable $e) {
            $categoryChartMonth = $latestChartDate
                ? Carbon::parse($latestChartDate)->startOfMonth()
                : now()->startOfMonth();
        }

        $categoryChartStart = $categoryChartMonth
            ->copy()
            ->startOfMonth();

        $categoryChartEnd = $categoryChartMonth
            ->copy()
            ->endOfMonth();

        $categoryChartLabel = $this->labelBulan(
            $categoryChartMonth
        );

        /*
        |--------------------------------------------------------------------------
        | Grafik Pelanggaran berdasarkan 22 kategori Form K3.2
        |--------------------------------------------------------------------------
        */
        $pelanggaranEventsForChart = MonitoringEvent::query()
            ->with([
                'reportUpload:id,nama_file',
            ])
            ->where('event_type', 'pelanggaran')
            ->whereBetween('event_date', [
                $categoryChartStart->toDateString(),
                $categoryChartEnd->toDateString(),
            ])
            ->select([
                'id',
                'report_upload_id',
                'event_name',
                'category',
                'description',
                'raw_data',
            ])
            ->get();

        $pelanggaranChart = $pelanggaranEventsForChart
            ->map(function ($event) {
                return $this->classifyPelanggaranEvent(
                    $event
                );
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->map(function ($total, $categoryName) {
                return (object) [
                    'event_name' => $categoryName,
                    'total' => (int) $total,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Grafik Kendala yang sudah dinormalisasi
        |--------------------------------------------------------------------------
        |
        | Nama Kendala yang memiliki arti sama disatukan sebelum dihitung.
        |
        */
        /*
        |--------------------------------------------------------------------------
        | Grafik Kendala yang sudah disterilkan
        |--------------------------------------------------------------------------
        */
        $kendalaEventsForChart = MonitoringEvent::query()
            ->with([
                'reportUpload:id,nama_file',
            ])
            ->where('event_type', 'kendala')
            ->whereBetween('event_date', [
                $categoryChartStart->toDateString(),
                $categoryChartEnd->toDateString(),
            ])
            ->select([
                'id',
                'report_upload_id',
                'event_name',
                'category',
                'description',
                'raw_data',
            ])
            ->get();

        $kendalaChart = $kendalaEventsForChart
            ->map(function ($event) {
                return $this->classifyKendalaEvent(
                    $event
                );
            })
            ->filter(function ($categoryName) {
                return $categoryName !== null
                    && trim($categoryName) !== '';
            })
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->map(function ($total, $categoryName) {
                return (object) [
                    'event_name' =>
                        $categoryName,

                    'total' =>
                        (int) $total,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Grafik Accident
        |--------------------------------------------------------------------------
        */
        $accidentChart = MonitoringEvent::query()
            ->select(
                'category',
                DB::raw('COUNT(*) as total')
            )
            ->where('event_type', 'accident')
            ->whereBetween('event_date', [
                $categoryChartStart->toDateString(),
                $categoryChartEnd->toDateString(),
            ])
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $row->category =
                    trim((string) $row->category) !== ''
                        ? $row->category
                        : 'Tidak diketahui';

                $row->total = (int) $row->total;

                return $row;
            });

        /*
        |--------------------------------------------------------------------------
        | Grafik Errorlog
        |--------------------------------------------------------------------------
        */
        $errorlogChart = MonitoringEvent::query()
            ->select(
                'event_name',
                DB::raw('COUNT(*) as total')
            )
            ->where('event_type', 'errorlog')
            ->whereBetween('event_date', [
                $categoryChartStart->toDateString(),
                $categoryChartEnd->toDateString(),
            ])
            ->whereNotNull('event_name')
            ->where('event_name', '!=', '')
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Grafik Skor Pengemudi
        |--------------------------------------------------------------------------
        |
        | Skor dikelompokkan berdasarkan nama AMT.
        | Nama K3-06.1 digunakan sebagai penulisan resmi.
        |
        */

        $skorPengemudiChart = $this->driverScoreService
            ->summarize(
                $categoryChartStart,
                $categoryChartEnd
            )
            ->filter(function ($item) {
                return $item->driver_name
                    !== 'AMT BELUM TERIDENTIFIKASI';
            })
            ->sortByDesc('total_risiko')
            ->take(10)
            ->values();

        return view('dashboard', compact(
            'totalFile',
            'totalPelanggaran',
            'totalKendala',
            'totalAccident',
            'totalErrorlog',
            'totalNopol',
            'pelanggaranChart',
            'kendalaChart',
            'accidentChart',
            'errorlogChart',
            'skorPengemudiChart',
            'comparisonCards',
            'trenBulanan',
            'comparisonTitle',
            'comparisonSubtitle',
            'trendTitle',
            'trendSubtitle',
            'trendPeriod',
            'visibleSeries',
            'trendMonthStart',
            'trendMonthEnd',
            'compareCurrentMonth',
            'comparePreviousMonth',
            'categoryChartLabel',
            'comparisonTrendMode',
            'comparisonTrendData',
            'totalPengemudi'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Halaman detail monitoring
    |--------------------------------------------------------------------------
    */
    public function detail(Request $request, string $jenis)
        {
            /*
            |--------------------------------------------------------------------------
            | Detail skor pengemudi
            |--------------------------------------------------------------------------
            */
            if ($jenis === 'skor-pengemudi') {
    /*
    |--------------------------------------------------------------------------
    | Menentukan bulan laporan
    |--------------------------------------------------------------------------
    */

    $latestScoreDate = MonitoringEvent::query()
        ->whereIn('event_type', [
            'pelanggaran',
            'kendala',
            'accident',
        ])
        ->whereNotNull('event_date')
        ->max('event_date');

    $defaultMonth = $latestScoreDate
        ? Carbon::parse($latestScoreDate)->format('Y-m')
        : now()->format('Y-m');

    $selectedMonth = trim(
        (string) $request->input(
            'bulan',
            $defaultMonth
        )
    );

    try {
        $scoreMonth = Carbon::createFromFormat(
            'Y-m',
            $selectedMonth
        )->startOfMonth();
    } catch (\Throwable $e) {
        $scoreMonth = Carbon::createFromFormat(
            'Y-m',
            $defaultMonth
        )->startOfMonth();

        $selectedMonth = $scoreMonth->format('Y-m');
    }

    $scoreStart = $scoreMonth
        ->copy()
        ->startOfMonth();

    $scoreEnd = $scoreMonth
        ->copy()
        ->endOfMonth();

    /*
    |--------------------------------------------------------------------------
    | Mengambil ringkasan skor
    |--------------------------------------------------------------------------
    */

    $search = trim(
        (string) $request->input(
            'search',
            ''
        )
    );

    $scoreRows = $this->driverScoreService
        ->summarize(
            $scoreStart,
            $scoreEnd,
            $search
        )
        ->sortByDesc('total_risiko')
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Filter status validasi
    |--------------------------------------------------------------------------
    */

    $validationStatus = trim(
        (string) $request->input(
            'validation_status',
            ''
        )
    );

    if ($validationStatus === 'registered') {
        $scoreRows = $scoreRows
            ->filter(function ($item) {
                return $item->registered_in_k3061 === true;
            })
            ->values();
    }

    if ($validationStatus === 'unregistered') {
        $scoreRows = $scoreRows
            ->filter(function ($item) {
                return (
                    $item->registered_in_k3061 === false
                    &&
                    $item->driver_name
                    !== 'AMT BELUM TERIDENTIFIKASI'
                );
            })
            ->values();
    }

    if ($validationStatus === 'unidentified') {
        $scoreRows = $scoreRows
            ->filter(function ($item) {
                return $item->driver_name
                    === 'AMT BELUM TERIDENTIFIKASI';
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Ringkasan bagian atas
    |--------------------------------------------------------------------------
    */

    $summaryStats = [
        'total_driver' =>
            $scoreRows->count(),

        'total_event' =>
            $scoreRows->sum('total_event'),

        'total_risiko' =>
            $scoreRows->sum('total_risiko'),

        'unidentified' =>
            $scoreRows
                ->where(
                    'driver_name',
                    'AMT BELUM TERIDENTIFIKASI'
                )
                ->sum('total_event'),
    ];

    /*
    |--------------------------------------------------------------------------
    | Pagination collection
    |--------------------------------------------------------------------------
    */

    $page = LengthAwarePaginator::resolveCurrentPage();

    $perPage = 25;

    $data = new LengthAwarePaginator(
        $scoreRows
            ->forPage(
                $page,
                $perPage
            )
            ->values(),
        $scoreRows->count(),
        $perPage,
        $page,
        [
            'path' =>
                $request->url(),

            'query' =>
                $request->query(),
        ]
    );

    $periodLabel = $scoreMonth
        ->locale('id')
        ->translatedFormat('F Y');

    return view(
        'monitoring-detail.skor-pengemudi',
        compact(
            'data',
            'periodLabel',
            'selectedMonth',
            'summaryStats',
            'validationStatus'
        )
    );
}

            $allowed = [
                'pelanggaran',
                'kendala',
                'accident',
                'errorlog',
            ];

            if (!in_array($jenis, $allowed, true)) {
                abort(404);
            }

            /*
            |--------------------------------------------------------------------------
            | Query utama
            |--------------------------------------------------------------------------
            */
            $query = MonitoringEvent::query()
                ->with('reportUpload')
                ->where('event_type', $jenis);

            /*
            |--------------------------------------------------------------------------
            | Pencarian bebas
            |--------------------------------------------------------------------------
            */
            if ($request->filled('search')) {
                $search = trim((string) $request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('nopol', 'like', '%' . $search . '%')
                        ->orWhere(
                            'driver_name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'tlpg',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'event_name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'category',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'severity',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'reportUpload',
                            function ($uploadQuery) use ($search) {
                                $uploadQuery->where(
                                    'nama_file',
                                    'like',
                                    '%' . $search . '%'
                                );
                            }
                        );
                });
            }

            /*
            |--------------------------------------------------------------------------
            | Filter tanggal
            |--------------------------------------------------------------------------
            */
            if ($request->filled('tanggal')) {
                $query->whereDate(
                    'event_date',
                    $request->tanggal
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Filter bulan
            |--------------------------------------------------------------------------
            */
            if ($request->filled('bulan')) {
                try {
                    $bulan = Carbon::createFromFormat(
                        'Y-m',
                        $request->bulan
                    );

                    $query->whereBetween('event_date', [
                        $bulan
                            ->copy()
                            ->startOfMonth()
                            ->toDateString(),

                        $bulan
                            ->copy()
                            ->endOfMonth()
                            ->toDateString(),
                    ]);
                } catch (\Throwable $e) {
                    // Abaikan input bulan yang tidak valid.
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Filter TLPG
            |--------------------------------------------------------------------------
            */
            if ($request->filled('tlpg')) {
                $query->where(
                    'tlpg',
                    $request->tlpg
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Filter Status Errorlog
            |--------------------------------------------------------------------------
            */
            if ($jenis === 'errorlog') {
                $statusErrorlog = trim(
                    (string) $request->input(
                        'status_errorlog',
                        ''
                    )
                );

                if (
                    in_array(
                        $statusErrorlog,
                        [
                            'closed',
                            'aktif',
                            'kosong',
                        ],
                        true
                    )
                ) {
                    $this->applyErrorlogStatusFilter(
                        $query,
                        $statusErrorlog
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Daftar jenis kejadian dan pemetaan nama Kendala
            |--------------------------------------------------------------------------
            */
            [
                $eventOptions,
                $eventNameMap,
            ] = $this->buildEventOptions($jenis);

            /*
            |--------------------------------------------------------------------------
            | Filter jenis kejadian
            |--------------------------------------------------------------------------
            */
            if ($request->filled('event_name')) {
                $this->applyEventNameFilter(
                    $query,
                    $jenis,
                    (string) $request->event_name,
                    $eventNameMap
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Ringkasan visual halaman detail
            |--------------------------------------------------------------------------
            |
            | Query sudah membawa seluruh filter aktif:
            | pencarian, tanggal, bulan, TLPG, jenis, dan status Errorlog.
            |
            */
            $detailSummaryQuery = clone $query;

            $detailSummary = $this->buildDetailSummary(
                $detailSummaryQuery,
                $jenis,
                $request
            );

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */
            $events = $query
                ->orderByDesc('event_date')
                ->orderByDesc('created_at')
                ->paginate(25)
                ->appends($request->query());

            /*
            |--------------------------------------------------------------------------
            | Rapikan nama kategori untuk tampilan tabel
            |--------------------------------------------------------------------------
            */
            if ($jenis === 'pelanggaran') {
                $events
                    ->getCollection()
                    ->transform(function ($event) {
                        $canonicalName =
                            $this->classifyPelanggaranEvent(
                                $event
                            );

                        $event->event_name =
                            $canonicalName
                            ?: 'Belum Terklasifikasi';

                        return $event;
                    });
            }

            if ($jenis === 'kendala') {
                $events
                    ->getCollection()
                    ->transform(function ($event) {
                        $canonicalName =
                            $this->classifyKendalaEvent(
                                $event
                            );

                        if ($canonicalName) {
                            $event->event_name =
                                $canonicalName;
                        }

                        return $event;
                    });
            }

            /*
            |--------------------------------------------------------------------------
            | Status monitoring Errorlog
            |--------------------------------------------------------------------------
            */
            if ($jenis === 'errorlog') {
                $events
                    ->getCollection()
                    ->transform(function ($event) {
                        $event->monitoring_status =
                            $this->resolveErrorlogMonitoringStatus(
                                $event->event_status,
                                $event->follow_up_status
                            );

                        return $event;
                    });
            }

            /*
            |--------------------------------------------------------------------------
            | Opsi TLPG
            |--------------------------------------------------------------------------
            */
            $tlpgOptions = MonitoringEvent::query()
                ->where('event_type', $jenis)
                ->whereNotNull('tlpg')
                ->where('tlpg', '!=', '')
                ->select('tlpg')
                ->distinct()
                ->orderBy('tlpg')
                ->pluck('tlpg');

            /*
            * Dipertahankan apabila Blade lama masih menggunakan
            * variabel kategoriOptions.
            */
            $kategoriOptions = $eventOptions;

            return view(
                'monitoring-detail.index',
                compact(
                    'events',
                    'jenis',
                    'tlpgOptions',
                    'eventOptions',
                    'kategoriOptions',
                    'detailSummary'
                )
            );
        }

    /*
    |--------------------------------------------------------------------------
    | Cetak PDF gabungan
    |--------------------------------------------------------------------------
    */
    public function cetakPdfGabungan(
        Request $request,
        string $jenis
    ) {
        $allowedJenis = [
            'pelanggaran',
            'kendala',
            'accident',
        ];

        if (!in_array($jenis, $allowedJenis, true)) {
            abort(404);
        }

        $query = MonitoringEvent::query()
            ->with('reportUpload')
            ->where('event_type', $jenis)
            ->whereNotNull('report_upload_id');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('nopol', 'like', '%' . $search . '%')
                    ->orWhere('driver_name', 'like', '%' . $search . '%')
                    ->orWhere('tlpg', 'like', '%' . $search . '%')
                    ->orWhere('event_name', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%')
                    ->orWhere('severity', 'like', '%' . $search . '%')
                    ->orWhereHas(
                        'reportUpload',
                        function ($uploadQuery) use ($search) {
                            $uploadQuery->where(
                                'nama_file',
                                'like',
                                '%' . $search . '%'
                            );
                        }
                    );
            });
        }

        if ($request->filled('tlpg')) {
            $query->where(
                'tlpg',
                $request->tlpg
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter status Errorlog
        |--------------------------------------------------------------------------
        |
        | Prioritas status:
        | 1. event_status
        | 2. follow_up_status jika event_status kosong
        |
        | Status selain Closed dan tidak kosong dianggap masih aktif.
        |
        */
        if (
            $jenis === 'errorlog' &&
            $request->filled('status_errorlog')
        ) {
            $this->applyErrorlogStatusFilter(
                $query,
                (string) $request->status_errorlog
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter jenis untuk cetak PDF
        |--------------------------------------------------------------------------
        |
        | Khusus Kendala, nama pilihan merupakan nama normalisasi.
        | Query akan mengambil seluruh variasi nama mentahnya.
        |
        */
        [
            $unusedEventOptions,
            $eventNameMap,
        ] = $this->buildEventOptions($jenis);

        if ($request->filled('event_name')) {
            $this->applyEventNameFilter(
                $query,
                $jenis,
                (string) $request->event_name,
                $eventNameMap
            );
        }

        $rentangCetak = $request->input(
            'rentang_cetak',
            'sesuai_filter'
        );

        $range = $this->resolveRentangCetakPdf(
            $request,
            $rentangCetak
        );

        if (
            $rentangCetak !== 'sesuai_filter' &&
            !$range
        ) {
            return back()->with(
                'error',
                'Pilih tanggal, minggu, atau bulan cetak terlebih dahulu.'
            );
        }

        if ($range) {
            $query->whereBetween('event_date', [
                $range['start']->toDateString(),
                $range['end']->toDateString(),
            ]);
        } else {
            if ($request->filled('tanggal')) {
                $query->whereDate(
                    'event_date',
                    $request->tanggal
                );
            }

            if ($request->filled('bulan')) {
                try {
                    $bulan = Carbon::createFromFormat(
                        'Y-m',
                        $request->bulan
                    );

                    $query->whereBetween('event_date', [
                        $bulan->copy()->startOfMonth()->toDateString(),
                        $bulan->copy()->endOfMonth()->toDateString(),
                    ]);
                } catch (\Throwable $e) {
                    // Abaikan jika format bulan tidak valid.
                }
            }
        }

        $reportIds = $query
            ->orderBy('event_date')
            ->orderBy('created_at')
            ->pluck('report_upload_id')
            ->unique()
            ->values();

        if ($reportIds->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada file PDF yang cocok dengan filter cetak.'
            );
        }

        $uploads = ReportUpload::whereIn(
            'id',
            $reportIds
        )
            ->get()
            ->keyBy('id');

        $pdf = new Fpdi();
        $mergedFileCount = 0;

        foreach ($reportIds as $reportId) {
            $upload = $uploads->get($reportId);

            if (!$upload) {
                continue;
            }

            $namaFile = $upload->nama_file ?? '';

            $extension = strtolower(
                pathinfo(
                    $namaFile,
                    PATHINFO_EXTENSION
                )
            );

            if ($extension !== 'pdf') {
                continue;
            }

            $filePath = $this->resolveReportUploadPdfPath(
                $upload
            );

            if (!$filePath || !is_file($filePath)) {
                continue;
            }

            try {
                $pageCount = $pdf->setSourceFile(
                    $filePath
                );

                for (
                    $pageNo = 1;
                    $pageNo <= $pageCount;
                    $pageNo++
                ) {
                    $templateId = $pdf->importPage(
                        $pageNo
                    );

                    $size = $pdf->getTemplateSize(
                        $templateId
                    );

                    $orientation =
                        $size['width'] > $size['height']
                            ? 'L'
                            : 'P';

                    $pdf->AddPage(
                        $orientation,
                        [
                            $size['width'],
                            $size['height'],
                        ]
                    );

                    $pdf->useTemplate($templateId);
                }

                $mergedFileCount++;
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($mergedFileCount === 0) {
            return back()->with(
                'error',
                'File ditemukan, tetapi tidak ada PDF yang bisa digabung.'
            );
        }

        $fileName =
            'gabungan-' .
            $jenis .
            '-' .
            now()->format('Ymd-His') .
            '.pdf';

        return response(
            $pdf->Output('S'),
            200
        )
            ->header(
                'Content-Type',
                'application/pdf'
            )
            ->header(
                'Content-Disposition',
                'inline; filename="' . $fileName . '"'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Membentuk data grafik tren normal
    |--------------------------------------------------------------------------
    */
    private function buildTrendData(
        string $period,
        ?string $latestDate,
        ?string $trendMonthStart = null,
        ?string $trendMonthEnd = null
    ): array {
        $baseDate = $latestDate
            ? Carbon::parse($latestDate)
            : now();

        $range = $this->getTrendRange(
            $period,
            $baseDate,
            $trendMonthStart,
            $trendMonthEnd
        );

        return match ($period) {
            'harian' => $this->buildDailyTrend(
                $range['start'],
                $range['end']
            ),

            'mingguan' => $this->buildWeeklyTrend(
                $range['start'],
                $range['end']
            ),

            'tahunan' => $this->buildYearlyTrend(
                $range['start'],
                $range['end']
            ),

            default => $this->buildMonthlyTrend(
                $range['start'],
                $range['end']
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Grafik harian
    |--------------------------------------------------------------------------
    */
    private function buildDailyTrend(
        Carbon $start,
        Carbon $end
    ): array {
        $data = [];

        $cursor = $start
            ->copy()
            ->startOfDay();

        $endDate = $end
            ->copy()
            ->endOfDay();

        while ($cursor->lte($endDate)) {
            $data[] = [
                'month' => $cursor->format('d M'),

                'pelanggaran' => $this->countByDateRange(
                    'pelanggaran',
                    $cursor->copy()->startOfDay(),
                    $cursor->copy()->endOfDay()
                ),

                'kendala' => $this->countByDateRange(
                    'kendala',
                    $cursor->copy()->startOfDay(),
                    $cursor->copy()->endOfDay()
                ),

                'accident' => $this->countByDateRange(
                    'accident',
                    $cursor->copy()->startOfDay(),
                    $cursor->copy()->endOfDay()
                ),

                'errorlog' => $this->countByDateRange(
                    'errorlog',
                    $cursor->copy()->startOfDay(),
                    $cursor->copy()->endOfDay()
                ),
            ];

            $cursor->addDay();
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Grafik mingguan normal
    |--------------------------------------------------------------------------
    */
    private function buildWeeklyTrend(
        Carbon $start,
        Carbon $end
    ): array {
        $data = [];

        $cursor = $start
            ->copy()
            ->startOfDay();

        $endDate = $end
            ->copy()
            ->endOfDay();

        while ($cursor->lte($endDate)) {
            $weekStart = $cursor
                ->copy()
                ->startOfDay();

            $weekEnd = $cursor
                ->copy()
                ->endOfWeek()
                ->endOfDay();

            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }

            $data[] = [
                'month' => $this->formatWeeklyRangeLabel(
                    $weekStart,
                    $weekEnd
                ),

                'pelanggaran' => $this->countByDateRange(
                    'pelanggaran',
                    $weekStart,
                    $weekEnd
                ),

                'kendala' => $this->countByDateRange(
                    'kendala',
                    $weekStart,
                    $weekEnd
                ),

                'accident' => $this->countByDateRange(
                    'accident',
                    $weekStart,
                    $weekEnd
                ),

                'errorlog' => $this->countByDateRange(
                    'errorlog',
                    $weekStart,
                    $weekEnd
                ),
            ];

            $cursor = $weekEnd
                ->copy()
                ->addDay()
                ->startOfDay();
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Grafik bulanan
    |--------------------------------------------------------------------------
    */
    private function buildMonthlyTrend(
        Carbon $start,
        Carbon $end
    ): array {
        $rows = MonitoringEvent::whereNotNull('event_date')
            ->whereIn('event_type', [
                'pelanggaran',
                'kendala',
                'accident',
                'errorlog',
            ])
            ->whereBetween('event_date', [
                $start
                    ->copy()
                    ->startOfMonth()
                    ->format('Y-m-d'),

                $end
                    ->copy()
                    ->endOfMonth()
                    ->format('Y-m-d'),
            ])
            ->selectRaw("
                YEAR(event_date) as tahun,
                MONTH(event_date) as bulan,

                SUM(
                    CASE
                        WHEN event_type = 'pelanggaran'
                        THEN 1
                        ELSE 0
                    END
                ) as pelanggaran,

                SUM(
                    CASE
                        WHEN event_type = 'kendala'
                        THEN 1
                        ELSE 0
                    END
                ) as kendala,

                SUM(
                    CASE
                        WHEN event_type = 'accident'
                        THEN 1
                        ELSE 0
                    END
                ) as accident,

                SUM(
                    CASE
                        WHEN event_type = 'errorlog'
                        THEN 1
                        ELSE 0
                    END
                ) as errorlog
            ")
            ->groupByRaw(
                'YEAR(event_date), MONTH(event_date)'
            )
            ->orderByRaw(
                'YEAR(event_date), MONTH(event_date)'
            )
            ->get();

        return $rows
            ->map(function ($row) {
                $tanggal = Carbon::create(
                    (int) $row->tahun,
                    (int) $row->bulan,
                    1
                );

                return [
                    'month' => $this->labelBulan(
                        $tanggal
                    ),

                    'pelanggaran' =>
                        (int) $row->pelanggaran,

                    'kendala' =>
                        (int) $row->kendala,

                    'accident' =>
                        (int) $row->accident,

                    'errorlog' =>
                        (int) $row->errorlog,
                ];
            })
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Grafik tahunan
    |--------------------------------------------------------------------------
    */
    private function buildYearlyTrend(
        Carbon $start,
        Carbon $end
    ): array {
        $rows = MonitoringEvent::whereNotNull('event_date')
            ->whereIn('event_type', [
                'pelanggaran',
                'kendala',
                'accident',
                'errorlog',
            ])
            ->whereBetween('event_date', [
                $start
                    ->copy()
                    ->startOfYear()
                    ->format('Y-m-d'),

                $end
                    ->copy()
                    ->endOfYear()
                    ->format('Y-m-d'),
            ])
            ->selectRaw("
                YEAR(event_date) as tahun,

                SUM(
                    CASE
                        WHEN event_type = 'pelanggaran'
                        THEN 1
                        ELSE 0
                    END
                ) as pelanggaran,

                SUM(
                    CASE
                        WHEN event_type = 'kendala'
                        THEN 1
                        ELSE 0
                    END
                ) as kendala,

                SUM(
                    CASE
                        WHEN event_type = 'accident'
                        THEN 1
                        ELSE 0
                    END
                ) as accident,

                SUM(
                    CASE
                        WHEN event_type = 'errorlog'
                        THEN 1
                        ELSE 0
                    END
                ) as errorlog
            ")
            ->groupByRaw('YEAR(event_date)')
            ->orderByRaw('YEAR(event_date)')
            ->get();

        return $rows
            ->map(function ($row) {
                return [
                    'month' => (string) $row->tahun,

                    'pelanggaran' =>
                        (int) $row->pelanggaran,

                    'kendala' =>
                        (int) $row->kendala,

                    'accident' =>
                        (int) $row->accident,

                    'errorlog' =>
                        (int) $row->errorlog,
                ];
            })
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Menentukan rentang grafik
    |--------------------------------------------------------------------------
    */
    private function getTrendRange(
        string $period,
        Carbon $baseDate,
        ?string $trendMonthStart = null,
        ?string $trendMonthEnd = null
    ): array {
        $customStart = $this->parseMonthInput(
            $trendMonthStart,
            false
        );

        $customEnd = $this->parseMonthInput(
            $trendMonthEnd,
            true
        );

        $minDate = MonitoringEvent::whereNotNull(
            'event_date'
        )->min('event_date');

        $maxDate = MonitoringEvent::whereNotNull(
            'event_date'
        )->max('event_date');

        $firstDataDate = $minDate
            ? Carbon::parse($minDate)
            : $baseDate->copy();

        $lastDataDate = $maxDate
            ? Carbon::parse($maxDate)
            : $baseDate->copy();

        if ($customStart && $customEnd) {
            if ($customStart->gt($customEnd)) {
                [$customStart, $customEnd] = [
                    $customEnd,
                    $customStart,
                ];
            }

            return [
                'start' => $customStart
                    ->copy()
                    ->startOfMonth(),

                'end' => $customEnd
                    ->copy()
                    ->endOfMonth(),
            ];
        }

        if ($customStart && !$customEnd) {
            return [
                'start' => $customStart
                    ->copy()
                    ->startOfMonth(),

                'end' => $lastDataDate
                    ->copy()
                    ->endOfMonth(),
            ];
        }

        if (!$customStart && $customEnd) {
            return [
                'start' => $firstDataDate
                    ->copy()
                    ->startOfMonth(),

                'end' => $customEnd
                    ->copy()
                    ->endOfMonth(),
            ];
        }

        if (
            in_array(
                $period,
                ['harian', 'mingguan'],
                true
            )
        ) {
            return [
                'start' => $baseDate
                    ->copy()
                    ->startOfMonth(),

                'end' => $baseDate
                    ->copy()
                    ->endOfMonth(),
            ];
        }

        if ($period === 'tahunan') {
            return [
                'start' => $firstDataDate
                    ->copy()
                    ->startOfYear(),

                'end' => $lastDataDate
                    ->copy()
                    ->endOfYear(),
            ];
        }

        return [
            'start' => $firstDataDate
                ->copy()
                ->startOfMonth(),

            'end' => $lastDataDate
                ->copy()
                ->endOfMonth(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Periode pembanding khusus
    |--------------------------------------------------------------------------
    */
    private function getCustomComparePeriod(
        ?string $currentMonth,
        ?string $previousMonth
    ): ?array {
        $currentStart = $this->parseMonthInput(
            $currentMonth,
            false
        );

        $previousStart = $this->parseMonthInput(
            $previousMonth,
            false
        );

        if (!$currentStart || !$previousStart) {
            return null;
        }

        return [
            'label' => 'Bulanan',

            'current_start' => $currentStart
                ->copy()
                ->startOfMonth(),

            'current_end' => $currentStart
                ->copy()
                ->endOfMonth(),

            'previous_start' => $previousStart
                ->copy()
                ->startOfMonth(),

            'previous_end' => $previousStart
                ->copy()
                ->endOfMonth(),

            'current_label' => $this->labelBulan(
                $currentStart
            ),

            'previous_label' => $this->labelBulan(
                $previousStart
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Mengubah input YYYY-MM menjadi Carbon
    |--------------------------------------------------------------------------
    */
    private function parseMonthInput(
        ?string $value,
        bool $endOfMonth = false
    ): ?Carbon {
        if (!$value) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat(
                'Y-m',
                $value
            );

            return $endOfMonth
                ? $date->copy()->endOfMonth()
                : $date->copy()->startOfMonth();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Menghitung event berdasarkan rentang tanggal
    |--------------------------------------------------------------------------
    */
    private function countByDateRange(
        string $eventType,
        Carbon $start,
        Carbon $end
    ): int {
        return MonitoringEvent::where(
            'event_type',
            $eventType
        )
            ->whereBetween('event_date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ])
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Periode perbandingan otomatis
    |--------------------------------------------------------------------------
    */
    private function getPeriodInfo(
        string $period,
        ?string $latestDate
    ): array {
        $baseDate = $latestDate
            ? Carbon::parse($latestDate)
            : now();

        if ($period === 'harian') {
            $currentStart = $baseDate
                ->copy()
                ->startOfMonth();

            $currentEnd = $baseDate
                ->copy()
                ->endOfMonth();

            $previousStart = $baseDate
                ->copy()
                ->subMonth()
                ->startOfMonth();

            $previousEnd = $baseDate
                ->copy()
                ->subMonth()
                ->endOfMonth();

            return [
                'label' => 'Harian',

                'current_start' => $currentStart,
                'current_end' => $currentEnd,

                'previous_start' => $previousStart,
                'previous_end' => $previousEnd,

                'current_label' =>
                    $currentStart->format('d M Y') .
                    ' - ' .
                    $currentEnd->format('d M Y'),

                'previous_label' =>
                    $previousStart->format('d M Y') .
                    ' - ' .
                    $previousEnd->format('d M Y'),
            ];
        }

        if ($period === 'mingguan') {
            $currentStart = $baseDate
                ->copy()
                ->startOfMonth();

            $currentEnd = $baseDate
                ->copy()
                ->endOfMonth();

            $previousStart = $baseDate
                ->copy()
                ->subMonth()
                ->startOfMonth();

            $previousEnd = $baseDate
                ->copy()
                ->subMonth()
                ->endOfMonth();

            return [
                'label' => 'Mingguan',

                'current_start' => $currentStart,
                'current_end' => $currentEnd,

                'previous_start' => $previousStart,
                'previous_end' => $previousEnd,

                'current_label' => $this->labelBulan(
                    $currentStart
                ),

                'previous_label' => $this->labelBulan(
                    $previousStart
                ),
            ];
        }

        if ($period === 'tahunan') {
            $currentStart = $baseDate
                ->copy()
                ->startOfYear();

            $currentEnd = $baseDate
                ->copy()
                ->endOfYear();

            $previousStart = $baseDate
                ->copy()
                ->subYear()
                ->startOfYear();

            $previousEnd = $baseDate
                ->copy()
                ->subYear()
                ->endOfYear();

            return [
                'label' => 'Tahunan',

                'current_start' => $currentStart,
                'current_end' => $currentEnd,

                'previous_start' => $previousStart,
                'previous_end' => $previousEnd,

                'current_label' =>
                    $currentStart->format('Y'),

                'previous_label' =>
                    $previousStart->format('Y'),
            ];
        }

        $currentStart = $baseDate
            ->copy()
            ->startOfMonth();

        $currentEnd = $baseDate
            ->copy()
            ->endOfMonth();

        $previousStart = $baseDate
            ->copy()
            ->subMonth()
            ->startOfMonth();

        $previousEnd = $baseDate
            ->copy()
            ->subMonth()
            ->endOfMonth();

        return [
            'label' => 'Bulanan',

            'current_start' => $currentStart,
            'current_end' => $currentEnd,

            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,

            'current_label' => $this->labelBulan(
                $currentStart
            ),

            'previous_label' => $this->labelBulan(
                $previousStart
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Perhitungan naik/turun
    |--------------------------------------------------------------------------
    */
    private function bandingkan(
        int $current,
        int $previous
    ): array {
        if ($previous === 0 && $current === 0) {
            return [
                'current' => $current,
                'previous' => $previous,
                'percentage' => 0,
                'direction' => 'stabil',
                'status' => 'Stabil',
            ];
        }

        if ($previous === 0 && $current > 0) {
            return [
                'current' => $current,
                'previous' => $previous,
                'percentage' => null,
                'direction' => 'baru',
                'status' => 'Data baru',
            ];
        }

        $percentage = round(
            (($current - $previous) / $previous) * 100,
            2
        );

        if ($percentage > 0) {
            return [
                'current' => $current,
                'previous' => $previous,
                'percentage' => $percentage,
                'direction' => 'naik',
                'status' => 'Perlu perhatian',
            ];
        }

        if ($percentage < 0) {
            return [
                'current' => $current,
                'previous' => $previous,
                'percentage' => $percentage,
                'direction' => 'turun',
                'status' => 'Membaik',
            ];
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'percentage' => 0,
            'direction' => 'stabil',
            'status' => 'Stabil',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Membuat data perbandingan grafik
    |--------------------------------------------------------------------------
    */
    private function buildComparisonTrendData(
        string $period,
        ?string $currentMonth,
        ?string $previousMonth,
        array $visibleSeries
    ): ?array {
        $current = $this->parseMonthInput(
            $currentMonth,
            false
        );

        $previous = $this->parseMonthInput(
            $previousMonth,
            false
        );

        if (!$current || !$previous) {
            return null;
        }

        $allowedSeries = [
            'pelanggaran',
            'kendala',
            'accident',
            'errorlog',
        ];

        $visibleSeries = array_values(
            array_unique(
                array_intersect(
                    $visibleSeries,
                    $allowedSeries
                )
            )
        );

        if (empty($visibleSeries)) {
            $visibleSeries = $allowedSeries;
        }

        if ($period === 'bulanan') {
            return $this->buildMonthlyComparisonData(
                $current,
                $previous,
                $visibleSeries
            );
        }

        if ($period === 'harian') {
            return $this->buildDailyComparisonData(
                $current,
                $previous,
                $visibleSeries
            );
        }

        if ($period === 'mingguan') {
            return $this->buildWeeklyComparisonData(
                $current,
                $previous,
                $visibleSeries
            );
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Perbandingan bulanan
    |--------------------------------------------------------------------------
    */
    private function buildMonthlyComparisonData(
        Carbon $current,
        Carbon $previous,
        array $visibleSeries
    ): array {
        $months = collect([
            $current->copy()->startOfMonth(),
            $previous->copy()->startOfMonth(),
        ])
            ->unique(
                fn (Carbon $month) =>
                    $month->format('Y-m')
            )
            ->sortBy(
                fn (Carbon $month) =>
                    $month->timestamp
            )
            ->values();

        $labels = $months
            ->map(
                fn (Carbon $month) =>
                    $this->labelBulan($month)
            )
            ->all();

        $datasets = [];

        foreach ($visibleSeries as $eventType) {
            $values = [];

            foreach ($months as $month) {
                $values[] = $this->countByDateRange(
                    $eventType,
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                );
            }

            $datasets[] = [
                'label' => $this->eventTypeLabel(
                    $eventType
                ),

                'data' => $values,

                'color' => $this->seriesColor(
                    $eventType
                ),
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Perbandingan harian
    |--------------------------------------------------------------------------
    */
    private function buildDailyComparisonData(
        Carbon $current,
        Carbon $previous,
        array $visibleSeries
    ): array {
        $currentDays = (int) $current->daysInMonth;
        $previousDays = (int) $previous->daysInMonth;

        $maxDays = max(
            $currentDays,
            $previousDays
        );

        $labels = [];

        for ($day = 1; $day <= $maxDays; $day++) {
            $labels[] = str_pad(
                (string) $day,
                2,
                '0',
                STR_PAD_LEFT
            );
        }

        $datasets = [];

        foreach ($visibleSeries as $eventType) {
            $currentData = [];
            $previousData = [];

            for ($day = 1; $day <= $maxDays; $day++) {
                if ($day <= $currentDays) {
                    $date = $current
                        ->copy()
                        ->day($day);

                    $currentData[] =
                        $this->countByDateRange(
                            $eventType,
                            $date->copy()->startOfDay(),
                            $date->copy()->endOfDay()
                        );
                } else {
                    $currentData[] = null;
                }

                if ($day <= $previousDays) {
                    $date = $previous
                        ->copy()
                        ->day($day);

                    $previousData[] =
                        $this->countByDateRange(
                            $eventType,
                            $date->copy()->startOfDay(),
                            $date->copy()->endOfDay()
                        );
                } else {
                    $previousData[] = null;
                }
            }

            $colorPair = $this->comparisonSeriesColors(
                $eventType
            );

            $datasets[] = [
                'label' =>
                    $this->eventTypeLabel($eventType) .
                    ' - ' .
                    $this->labelBulan($current),

                'data' => $currentData,

                'color' => $colorPair['current'],
            ];

            $datasets[] = [
                'label' =>
                    $this->eventTypeLabel($eventType) .
                    ' - ' .
                    $this->labelBulan($previous),

                'data' => $previousData,

                'color' => $colorPair['previous'],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Perbandingan mingguan dengan label tanggal asli
    |--------------------------------------------------------------------------
    */
    private function buildWeeklyComparisonData(
        Carbon $current,
        Carbon $previous,
        array $visibleSeries
    ): array {
        $currentRanges = $this->buildWeeklyRanges(
            $current
        );

        $previousRanges = $this->buildWeeklyRanges(
            $previous
        );

        $maxWeeks = max(
            count($currentRanges),
            count($previousRanges)
        );

        $labels = [];

        for ($index = 0; $index < $maxWeeks; $index++) {
            $currentLabel =
                $currentRanges[$index]['label'] ?? '-';

            $previousLabel =
                $previousRanges[$index]['label'] ?? '-';

            /*
             * Contoh:
             * 01–03 Mei / 01–07 Jun
             */
            $labels[] =
                $currentLabel .
                ' / ' .
                $previousLabel;
        }

        $datasets = [];

        foreach ($visibleSeries as $eventType) {
            $currentData = [];

            foreach ($currentRanges as $range) {
                $currentData[] = $this->countByDateRange(
                    $eventType,
                    $range['start'],
                    $range['end']
                );
            }

            $previousData = [];

            foreach ($previousRanges as $range) {
                $previousData[] = $this->countByDateRange(
                    $eventType,
                    $range['start'],
                    $range['end']
                );
            }

            $currentData = array_pad(
                $currentData,
                $maxWeeks,
                null
            );

            $previousData = array_pad(
                $previousData,
                $maxWeeks,
                null
            );

            $colorPair = $this->comparisonSeriesColors(
                $eventType
            );

            $datasets[] = [
                'label' =>
                    $this->eventTypeLabel($eventType) .
                    ' - ' .
                    $this->labelBulan($current),

                'data' => $currentData,

                'color' => $colorPair['current'],
            ];

            $datasets[] = [
                'label' =>
                    $this->eventTypeLabel($eventType) .
                    ' - ' .
                    $this->labelBulan($previous),

                'data' => $previousData,

                'color' => $colorPair['previous'],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Membentuk rentang minggu dalam satu bulan
    |--------------------------------------------------------------------------
    */
    private function buildWeeklyRanges(
        Carbon $month
    ): array {
        $ranges = [];

        $cursor = $month
            ->copy()
            ->startOfMonth()
            ->startOfDay();

        $endMonth = $month
            ->copy()
            ->endOfMonth()
            ->endOfDay();

        while ($cursor->lte($endMonth)) {
            $weekStart = $cursor
                ->copy()
                ->startOfDay();

            /*
             * Akhir minggu hari Minggu.
             * Rentang tidak boleh keluar dari bulan.
             */
            $weekEnd = $cursor
                ->copy()
                ->endOfWeek()
                ->endOfDay();

            if ($weekEnd->gt($endMonth)) {
                $weekEnd = $endMonth->copy();
            }

            $ranges[] = [
                'start' => $weekStart,
                'end' => $weekEnd,

                'label' => $this->formatWeeklyRangeLabel(
                    $weekStart,
                    $weekEnd
                ),
            ];

            $cursor = $weekEnd
                ->copy()
                ->addDay()
                ->startOfDay();
        }

        return $ranges;
    }

    /*
    |--------------------------------------------------------------------------
    | Format label rentang minggu
    |--------------------------------------------------------------------------
    */
    private function formatWeeklyRangeLabel(
        Carbon $start,
        Carbon $end
    ): string {
        $startMonth = (int) $start->format('n');
        $endMonth = (int) $end->format('n');

        if (
            $startMonth === $endMonth &&
            $start->format('Y') === $end->format('Y')
        ) {
            return
                $start->format('d') .
                '–' .
                $end->format('d') .
                ' ' .
                $this->bulanPendek($startMonth);
        }

        return
            $start->format('d') .
            ' ' .
            $this->bulanPendek($startMonth) .
            '–' .
            $end->format('d') .
            ' ' .
            $this->bulanPendek($endMonth);
    }

    /*
    |--------------------------------------------------------------------------
    | Nama jenis data
    |--------------------------------------------------------------------------
    */
    private function eventTypeLabel(
        string $eventType
    ): string {
        return [
            'pelanggaran' => 'Pelanggaran',
            'kendala' => 'Kendala',
            'accident' => 'Accident',
            'errorlog' => 'Errorlog',
        ][$eventType] ?? ucfirst($eventType);
    }

    /*
    |--------------------------------------------------------------------------
    | Warna jenis data
    |--------------------------------------------------------------------------
    */
    private function seriesColor(
        string $eventType
    ): string {
        return [
            'pelanggaran' => '#3b82f6',
            'kendala' => '#fb7185',
            'accident' => '#fb923c',
            'errorlog' => '#fbbf24',
        ][$eventType] ?? '#64748b';
    }

    /*
    |--------------------------------------------------------------------------
    | Warna dua bulan pembanding
    |--------------------------------------------------------------------------
    */
    private function comparisonSeriesColors(
        string $eventType
    ): array {
        return [
            'pelanggaran' => [
                'current' => '#2563eb',
                'previous' => '#06b6d4',
            ],

            'kendala' => [
                'current' => '#f43f5e',
                'previous' => '#8b5cf6',
            ],

            'accident' => [
                'current' => '#f97316',
                'previous' => '#eab308',
            ],

            'errorlog' => [
                'current' => '#14b8a6',
                'previous' => '#64748b',
            ],
        ][$eventType] ?? [
            'current' => '#2563eb',
            'previous' => '#ef4444',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Rentang cetak PDF
    |--------------------------------------------------------------------------
    */
    private function resolveRentangCetakPdf(
        Request $request,
        string $rentangCetak
    ): ?array {
        try {
            if (
                $rentangCetak === 'harian' &&
                $request->filled('cetak_tanggal')
            ) {
                $tanggal = Carbon::parse(
                    $request->cetak_tanggal
                );

                return [
                    'start' => $tanggal
                        ->copy()
                        ->startOfDay(),

                    'end' => $tanggal
                        ->copy()
                        ->endOfDay(),
                ];
            }

            if (
                $rentangCetak === 'mingguan' &&
                $request->filled('cetak_minggu')
            ) {
                if (
                    preg_match(
                        '/^(\d{4})-W(\d{2})$/',
                        $request->cetak_minggu,
                        $match
                    )
                ) {
                    $tahun = (int) $match[1];
                    $minggu = (int) $match[2];

                    $start = Carbon::now()
                        ->setISODate(
                            $tahun,
                            $minggu
                        )
                        ->startOfWeek();

                    return [
                        'start' => $start,

                        'end' => $start
                            ->copy()
                            ->endOfWeek(),
                    ];
                }
            }

            if (
                $rentangCetak === 'bulanan' &&
                $request->filled('cetak_bulan')
            ) {
                $bulan = Carbon::createFromFormat(
                    'Y-m',
                    $request->cetak_bulan
                );

                return [
                    'start' => $bulan
                        ->copy()
                        ->startOfMonth(),

                    'end' => $bulan
                        ->copy()
                        ->endOfMonth(),
                ];
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Mencari lokasi file PDF
    |--------------------------------------------------------------------------
    */
    private function resolveReportUploadPdfPath(
        ?ReportUpload $upload
    ): ?string {
        if (!$upload || !$upload->path_file) {
            return null;
        }

        $path = $upload->path_file;

        if (is_file($path)) {
            return $path;
        }

        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        $candidate1 = storage_path(
            'app/' . $path
        );

        if (is_file($candidate1)) {
            return $candidate1;
        }

        $candidate2 = storage_path(
            'app/public/' . $path
        );

        if (is_file($candidate2)) {
            return $candidate2;
        }

        $candidate3 = public_path(
            'storage/' . $path
        );

        if (is_file($candidate3)) {
            return $candidate3;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Format input bulan
    |--------------------------------------------------------------------------
    */
    private function formatMonthInputLabel(
        ?string $value
    ): string {
        $date = $this->parseMonthInput(
            $value,
            false
        );

        return $date
            ? $this->labelBulan($date)
            : '-';
    }

    /*
    |--------------------------------------------------------------------------
    | Nama bulan pendek
    |--------------------------------------------------------------------------
    */
    private function bulanPendek(
        int $bulan
    ): string {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ][$bulan] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | Label bulan dan tahun
    |--------------------------------------------------------------------------
    */
    private function labelBulan(
        Carbon $date
    ): string {
        return
            $this->bulanPendek(
                (int) $date->format('n')
            ) .
            ' ' .
            $date->format('Y');
    }

    /*
|--------------------------------------------------------------------------
| Membuat opsi jenis kejadian
|--------------------------------------------------------------------------
*/

    private function buildEventOptions(
        string $jenis
    ): array {
        if ($jenis === 'pelanggaran') {
            $map = array_fill_keys(
                $this->pelanggaranMaster(),
                []
            );

            $events = MonitoringEvent::query()
                ->with([
                    'reportUpload:id,nama_file',
                ])
                ->where('event_type', 'pelanggaran')
                ->select([
                    'id',
                    'report_upload_id',
                    'event_name',
                    'category',
                    'description',
                    'raw_data',
                ])
                ->get();

            foreach ($events as $event) {
                $canonicalName =
                    $this->classifyPelanggaranEvent(
                        $event
                    );

                if (
                    !$canonicalName ||
                    !array_key_exists(
                        $canonicalName,
                        $map
                    )
                ) {
                    continue;
                }

                $map[$canonicalName][] =
                    $event->id;
            }

            foreach ($map as $canonicalName => $ids) {
                $map[$canonicalName] =
                    array_values(
                        array_unique($ids)
                    );
            }

            return [
                collect(
                    $this->pelanggaranMaster()
                ),
                $map,
            ];
        }

        if ($jenis === 'kendala') {
            $events = MonitoringEvent::query()
                ->with([
                    'reportUpload:id,nama_file',
                ])
                ->where('event_type', 'kendala')
                ->select([
                    'id',
                    'report_upload_id',
                    'event_name',
                    'category',
                    'description',
                    'raw_data',
                ])
                ->get();

            $map = [];

            foreach ($events as $event) {
                $canonicalName =
                    $this->classifyKendalaEvent(
                        $event
                    );

                if (!$canonicalName) {
                    continue;
                }

                if (!isset($map[$canonicalName])) {
                    $map[$canonicalName] = [];
                }

                $map[$canonicalName][] =
                    $event->id;
            }

            foreach ($map as $canonicalName => $ids) {
                $map[$canonicalName] =
                    array_values(
                        array_unique($ids)
                    );
            }

            uksort(
                $map,
                'strnatcasecmp'
            );

            return [
                collect(
                    array_keys($map)
                )->values(),
                $map,
            ];
        }

        $eventOptions = MonitoringEvent::query()
            ->where('event_type', $jenis)
            ->whereNotNull('event_name')
            ->where('event_name', '!=', '')
            ->distinct()
            ->orderBy('event_name')
            ->pluck('event_name');

        return [
            $eventOptions,
            [],
        ];
    }


/*
|--------------------------------------------------------------------------
| Menerapkan filter jenis kejadian
|--------------------------------------------------------------------------
*/

    private function applyEventNameFilter(
        $query,
        string $jenis,
        string $selectedEventName,
        array $eventFilterMap
    ): void {
        if (
            in_array(
                $jenis,
                ['pelanggaran', 'kendala'],
                true
            )
        ) {
            $selectedKey = null;

            foreach (
                array_keys($eventFilterMap)
                as $canonicalName
            ) {
                if (
                    strcasecmp(
                        trim($canonicalName),
                        trim($selectedEventName)
                    ) === 0
                ) {
                    $selectedKey = $canonicalName;
                    break;
                }
            }

            if (
                $selectedKey !== null &&
                !empty(
                    $eventFilterMap[$selectedKey]
                )
            ) {
                $query->whereIn(
                    'monitoring_events.id',
                    $eventFilterMap[$selectedKey]
                );

                return;
            }

            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(
            'event_name',
            $selectedEventName
        );
    }


/*
|--------------------------------------------------------------------------
| Normalisasi nama Kendala
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Menggabungkan seluruh informasi Kendala dari PDF
|--------------------------------------------------------------------------
*/

    private function pelanggaranMaster(): array
    {
        return [
            'Menerima Penumpang Selain AMT',
            'Mengemudi Lebih dari 4 Jam',
            'Over Speed',
            'Perlambatan Mendadak',
            'Akselerasi Mendadak',
            'Tikungan Tajam',
            'Melebihi Batas Waktu Parkir',
            'Seat Belt',
            'Keluar Rute',
            'Berganti AMT Tanpa Lisensi',
            'Menggunakan Handphone / Gadget',
            'Merokok / Vape',
            'Menutup / Mengubah Posisi CAM',
            'Merusak / Melepas Device (GPS / CAM)',
            'Pengurangan Bahan Bakar',
            'Berganti AMT Yang Tidak Berlisensi (Accident)',
            'Pengemudi Kelelahan (Accident)',
            'Mengemudi Tidak Baik (Napza / Alkohol)',
            'Menghilangkan Sinyal GPS (Jammer)',
            'Geolokasi (Blackzone & Redzone)',
            'Pelecehan Verbal',
            'Mengintervensi, Mengancam / Bekerja Sama Dengan Petugas RTC',
        ];
    }

    private function classifyPelanggaranEvent(
        MonitoringEvent $event
    ): ?string {
        $rawData = $event->raw_data;

        if (is_array($rawData) || is_object($rawData)) {
            $rawDataText = json_encode(
                $rawData,
                JSON_UNESCAPED_UNICODE
            );
        } else {
            $rawDataText = (string) $rawData;
        }

        $fileName = optional(
            $event->reportUpload
        )->nama_file;

        /*
         * event_name dan nama file diprioritaskan karena umumnya
         * paling jelas menunjukkan jenis pelanggaran.
         */
        $primaryText = implode(
            ' ',
            array_filter([
                $event->event_name,
                $fileName,
            ])
        );

        $result =
            $this->classifyPelanggaranText(
                $primaryText
            );

        if ($result) {
            return $result;
        }

        $secondaryText = implode(
            ' ',
            array_filter([
                $event->event_name,
                $event->category,
                $event->description,
                $rawDataText,
                $fileName,
            ])
        );

        $result =
            $this->classifyPelanggaranText(
                $secondaryText
            );

        if ($result) {
            return $result;
        }

        return $this->closestPelanggaranMaster(
            (string) $event->event_name
        );
    }

    private function classifyPelanggaranText(
        string $value
    ): ?string {
        if (trim($value) === '') {
            return null;
        }

        $text = strtoupper(
            Str::ascii($value)
        );

        $text = preg_replace(
            '/[^A-Z0-9]+/',
            ' ',
            $text
        );

        $text = preg_replace(
            '/\s+/',
            ' ',
            trim($text)
        );

        /*
         * Kategori yang lebih spesifik diperiksa terlebih dahulu
         * agar tidak tertukar dengan kategori yang lebih umum.
         */

        if (
            preg_match(
                '/\b(?:MENERIMA|MEMBAWA)\s+PENUMPANG\s+SELAIN\s+AMT\b/',
                $text
            )
        ) {
            return 'Menerima Penumpang Selain AMT';
        }

        if (
            preg_match(
                '/\b(?:MENGEMUDI|BERKENDARA)\s+' .
                '(?:LEBIH\s+DARI|DIATAS|DI\s+ATAS)\s+4\s*JAM\b/',
                $text
            ) ||
            str_contains(
                $text,
                'MENGEMUDI 4 JAM'
            )
        ) {
            return 'Mengemudi Lebih dari 4 Jam';
        }

        if (
            preg_match(
                '/\bOVER\s*SPEED\b|\bOVERSPEED\b|\bKECEPATAN\s+BERLEBIH\b/',
                $text
            )
        ) {
            return 'Over Speed';
        }

        if (
            str_contains(
                $text,
                'PERLAMBATAN MENDADAK'
            ) ||
            str_contains(
                $text,
                'HARSH BRAKING'
            ) ||
            str_contains(
                $text,
                'HARD BRAKING'
            )
        ) {
            return 'Perlambatan Mendadak';
        }

        if (
            str_contains(
                $text,
                'AKSELERASI MENDADAK'
            ) ||
            str_contains(
                $text,
                'RAPID ACCELERATION'
            ) ||
            str_contains(
                $text,
                'HARSH ACCELERATION'
            )
        ) {
            return 'Akselerasi Mendadak';
        }

        if (
            str_contains(
                $text,
                'TIKUNGAN TAJAM'
            ) ||
            str_contains(
                $text,
                'SHARP TURN'
            ) ||
            str_contains(
                $text,
                'HARSH CORNERING'
            )
        ) {
            return 'Tikungan Tajam';
        }

        if (
            preg_match(
                '/\bMELEBIHI\s+BATAS\s+WAKTU(?:\s+PARKIR)?\b/',
                $text
            ) ||
            str_contains(
                $text,
                'BATAS WAKTU PARKIR'
            )
        ) {
            return 'Melebihi Batas Waktu Parkir';
        }

        if (
            str_contains($text, 'SEAT BELT') ||
            str_contains($text, 'SEATBELT') ||
            str_contains($text, 'SABUK PENGAMAN')
        ) {
            return 'Seat Belt';
        }

        if (
            str_contains($text, 'KELUAR RUTE') ||
            str_contains($text, 'OFF ROUTE') ||
            str_contains($text, 'PENYIMPANGAN RUTE')
        ) {
            return 'Keluar Rute';
        }

        /*
         * Kategori "Yang Tidak Berlisensi" dibedakan dari
         * frasa "Tanpa Lisensi" sesuai master K3.2.
         */
        if (
            preg_match(
                '/\b(?:BERGANTI|PERGANTIAN|GANTI)\s+AMT\b/',
                $text
            ) &&
            preg_match(
                '/\bYANG\s+TIDAK\s+BERLISENSI\b/',
                $text
            )
        ) {
            return 'Berganti AMT Yang Tidak Berlisensi (Accident)';
        }

        if (
            preg_match(
                '/\b(?:BERGANTI|PERGANTIAN|GANTI)\s+AMT\b/',
                $text
            ) &&
            preg_match(
                '/\b(?:TANPA\s+LISENSI|TIDAK\s+BERLISENSI)\b/',
                $text
            )
        ) {
            return 'Berganti AMT Tanpa Lisensi';
        }

        if (
            preg_match(
                '/\b(?:HANDPHONE|HAND\s*PHONE|GADGET|PONSEL|TELEPON|HP)\b/',
                $text
            )
        ) {
            return 'Menggunakan Handphone / Gadget';
        }

        if (
            preg_match(
                '/\b(?:MEROKOK|ROKOK|VAPE|VAPING)\b/',
                $text
            )
        ) {
            return 'Merokok / Vape';
        }

        /*
         * Posisi CAM diperiksa sebelum kategori merusak/melepas.
         */
        if (
            preg_match(
                '/\b(?:MENUTUP|TERTUTUP|MENGUBAH|MERUBAH|' .
                'MEMINDAH|GESER)\b.{0,50}' .
                '\b(?:CAM|CAMERA|KAMERA)\b/',
                $text
            )
        ) {
            return 'Menutup / Mengubah Posisi CAM';
        }

        if (
            str_contains($text, 'JAMMER') ||
            str_contains($text, 'JAMMING') ||
            preg_match(
                '/\bMENGHILANGKAN\s+SINYAL\s+GPS\b/',
                $text
            )
        ) {
            return 'Menghilangkan Sinyal GPS (Jammer)';
        }

        if (
            preg_match(
                '/\b(?:MERUSAK|MELEPAS|MENCABUT|MEMUTUS)\b.{0,60}' .
                '\b(?:DEVICE|GPS|CAM|CAMERA|KAMERA)\b/',
                $text
            )
        ) {
            return 'Merusak / Melepas Device (GPS / CAM)';
        }

        if (
            str_contains(
                $text,
                'PENGURANGAN BAHAN BAKAR'
            ) ||
            str_contains(
                $text,
                'PENGURANGAN BBM'
            ) ||
            str_contains(
                $text,
                'PENCURIAN BBM'
            ) ||
            str_contains(
                $text,
                'FUEL THEFT'
            )
        ) {
            return 'Pengurangan Bahan Bakar';
        }

        if (
            preg_match(
                '/\b(?:KELELAHAN|MENGANTUK|MICROSLEEP|FATIGUE)\b/',
                $text
            )
        ) {
            return 'Pengemudi Kelelahan (Accident)';
        }

        if (
            preg_match(
                '/\b(?:NAPZA|NARKOBA|ALKOHOL|MABUK|OBAT\s+TERLARANG)\b/',
                $text
            )
        ) {
            return 'Mengemudi Tidak Baik (Napza / Alkohol)';
        }

        if (
            preg_match(
                '/\b(?:BLACK\s*ZONE|BLACKZONE|RED\s*ZONE|REDZONE|' .
                'GEOLOKASI)\b/',
                $text
            )
        ) {
            return 'Geolokasi (Blackzone & Redzone)';
        }

        if (
            str_contains(
                $text,
                'PELECEHAN VERBAL'
            ) ||
            str_contains(
                $text,
                'KEKERASAN VERBAL'
            ) ||
            str_contains(
                $text,
                'VERBAL ABUSE'
            )
        ) {
            return 'Pelecehan Verbal';
        }

        if (
            preg_match(
                '/\b(?:INTERVENSI|MENGINTERVENSI|MENGANCAM|ANCAMAN)\b/',
                $text
            ) ||
            preg_match(
                '/\bBEKERJA\s+SAMA\b.{0,50}\bPETUGAS\s+RTC\b/',
                $text
            )
        ) {
            return 'Mengintervensi, Mengancam / Bekerja Sama Dengan Petugas RTC';
        }

        return null;
    }

    private function closestPelanggaranMaster(
        string $value
    ): ?string {
        $source =
            $this->normalizePelanggaranMatch(
                $value
            );

        if ($source === '') {
            return null;
        }

        $bestCategory = null;
        $bestPercentage = 0.0;

        foreach (
            $this->pelanggaranMaster()
            as $category
        ) {
            $target =
                $this->normalizePelanggaranMatch(
                    $category
                );

            $percentage = 0.0;

            similar_text(
                $source,
                $target,
                $percentage
            );

            if ($percentage > $bestPercentage) {
                $bestPercentage = $percentage;
                $bestCategory = $category;
            }
        }

        return $bestPercentage >= 58
            ? $bestCategory
            : null;
    }

    private function normalizePelanggaranMatch(
        string $value
    ): string {
        $value = strtoupper(
            Str::ascii($value)
        );

        $value = preg_replace(
            '/[^A-Z0-9]+/',
            '',
            $value
        );

        return trim($value);
    }

private function classifyKendalaEvent(
    MonitoringEvent $event
): ?string {
    $rawData = $event->raw_data;

    /*
     * raw_data dapat berupa array jika sudah memakai cast,
     * atau string JSON jika belum memakai cast.
     */
    if (is_array($rawData)) {
        $rawDataText = json_encode(
            $rawData,
            JSON_UNESCAPED_UNICODE
        );
    } elseif (is_object($rawData)) {
        $rawDataText = json_encode(
            $rawData,
            JSON_UNESCAPED_UNICODE
        );
    } else {
        $rawDataText =
            (string) $rawData;
    }

    $fileName = optional(
        $event->reportUpload
    )->nama_file;

    /*
     * Informasi yang dianalisis:
     * - jenis hasil importer
     * - kategori
     * - uraian/isi PDF
     * - data mentah parser
     * - nama file PDF
     */
    $fullText = implode(
        ' ',
        array_filter([
            $event->event_name,
            $event->category,
            $event->description,
            $rawDataText,
            $fileName,
        ])
    );

    return $this->classifyKendalaText(
        $fullText,
        $event->event_name
    );
}

        /*
        |--------------------------------------------------------------------------
        | Sterilisasi dan klasifikasi Kendala
        |--------------------------------------------------------------------------
        */
        private function classifyKendalaText(
            string $fullText,
            ?string $fallbackName = null
        ): ?string {
            if (
                trim($fullText) === '' &&
                (!$fallbackName || trim($fallbackName) === '')
            ) {
                return null;
            }

            $text = strtoupper(
                Str::ascii(
                    $fullText
                )
            );

            $text = str_replace(
                [
                    "\r",
                    "\n",
                    "\t",
                ],
                ' ',
                $text
            );

            /*
            * Hilangkan keterangan peralihan agar tidak menjadi
            * kategori tersendiri.
            */
            $text = preg_replace(
                '/\(\s*PERALIHAN\s+DARI[^)]*\)/',
                ' ',
                $text
            );

            $text = preg_replace(
                '/\bPERALIHAN\s+DARI\s+' .
                '(?:TLPG\s+)?' .
                '(?:MEM\s+GRESIK|TJ\s+PERAK|TJ\s+WANGI|' .
                'MANGGIS|LOMBOK|BIMA)\b/',
                ' ',
                $text
            );

            $text = preg_replace(
                '/\s+/',
                ' ',
                trim($text)
            );

            /*
            |--------------------------------------------------------------------------
            | 1. Batas waktu parkir
            |--------------------------------------------------------------------------
            |
            | Diperiksa sebelum kategori kendaraan agar frasa lain
            | tidak salah dikelompokkan.
            */
            if (
                preg_match(
                    '/\bMELEBIHI\s+BATAS\s+WAKTU' .
                    '(?:\s+PARKIR)?\b/',
                    $text
                )
            ) {
                return 'Melebihi Batas Waktu Parkir';
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Semua yang memiliki kata utuh BAN
            |--------------------------------------------------------------------------
            |
            | Termasuk:
            | - Perbaikan Ban
            | - Ganti Ban
            | - Pergantian Ban
            | - Ban Bocor
            | - Indikasi Perbaikan Ban
            |
            | Semuanya disatukan menjadi Perbaikan Ban.
            */
            if (
                preg_match(
                    '/\bBAN\b/',
                    $text
                )
            ) {
                return 'Perbaikan Ban';
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Kendala sistem/link BBM
            |--------------------------------------------------------------------------
            */
            if (
                preg_match(
                    '/\b(?:TROUBLE|TROBLE)\s+LINK\s+BBM\b/',
                    $text
                ) ||
                preg_match(
                    '/\bLINK\s+BBM\b/',
                    $text
                )
            ) {
                return 'Kendala Sistem BBM';
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Service dan perawatan
            |--------------------------------------------------------------------------
            */
            if (
                preg_match(
                    '/\b(?:' .
                        'SERVICE|' .
                        'SERVIS|' .
                        'MAINTENANCE|' .
                        'PERAWATAN|' .
                        'TUNE\s*UP|' .
                        'CHECK\s*UP|' .
                        'GENERAL\s+CHECK|' .
                        'OVERHAUL' .
                    ')\b/',
                    $text
                )
            ) {
                return 'Perbaikan Kendaraan';
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Komponen mekanis kendaraan
            |--------------------------------------------------------------------------
            */
            if (
                preg_match(
                    '/\b(?:' .
                        'MESIN|' .
                        'OLI|' .
                        'RADIATOR|' .
                        'REM|' .
                        'KAMPAS|' .
                        'KOPLING|' .
                        'AKI|' .
                        'ACCU|' .
                        'ALTERNATOR|' .
                        'DINAMO|' .
                        'TRANSMISI|' .
                        'GEARBOX|' .
                        'GARDAN|' .
                        'KNALPOT|' .
                        'LAMPU|' .
                        'WIPER|' .
                        'KACA|' .
                        'PINTU|' .
                        'KOMPRESOR|' .
                        'SUSPENSI|' .
                        'SHOCK|' .
                        'KAKI[\s-]*KAKI|' .
                        'STEERING|' .
                        'POWER\s+STEERING|' .
                        'KLAKSON|' .
                        'KABEL|' .
                        'CHAMBER|' .
                        'TROMOL|' .
                        'AS\s+RODA|' .
                        'VELG|' .
                        'BUMPER|' .
                        'BODY|' .
                        'CHASIS|' .
                        'SASIS' .
                    ')\b/',
                    $text
                )
            ) {
                return 'Perbaikan Kendaraan';
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Kata umum kerusakan/perbaikan
            |--------------------------------------------------------------------------
            */
            if (
                preg_match(
                    '/\b(?:' .
                        'PERBAIKAN|' .
                        'KERUSAKAN|' .
                        'RUSAK|' .
                        'MATI|' .
                        'BOCOR|' .
                        'PATAH|' .
                        'LEPAS|' .
                        'GANTI|' .
                        'PERGANTIAN' .
                    ')\b/',
                    $text
                )
            ) {
                return 'Perbaikan Kendaraan';
            }

            /*
            |--------------------------------------------------------------------------
            | 7. Over speed
            |--------------------------------------------------------------------------
            */
            if (
                preg_match(
                    '/\bOVER\s*SPEED\b|\bOVERSPEED\b/',
                    $text
                )
            ) {
                return 'Over Speed';
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Kendala baru yang belum dikenali
            |--------------------------------------------------------------------------
            |
            | Kendala tetap otomatis masuk ke dropdown.
            | Nama diambil dari event_name dan dirapikan.
            */
            $fallback = $fallbackName
                ?: $fullText;

            $fallback = strtoupper(
                Str::ascii(
                    trim($fallback)
                )
            );

            /*
            * Buang keterangan peralihan dari nama cadangan.
            */
            $fallback = preg_replace(
                '/\(\s*PERALIHAN\s+DARI[^)]*\)/',
                ' ',
                $fallback
            );

            $fallback = preg_replace(
                '/^[\s\)\(\-\:\.;,]+/',
                '',
                $fallback
            );

            $fallback = preg_replace(
                '/^INDIKASI\s+/',
                '',
                $fallback
            );

            $fallback = preg_replace(
                '/\s+/',
                ' ',
                trim($fallback)
            );

            if ($fallback === '') {
                return 'Kendala Lainnya';
            }

            $result = Str::title(
                Str::lower(
                    $fallback
                )
            );

            /*
            * Pertahankan beberapa singkatan.
            */
            $acronyms = [
                '/\bTlpg\b/i' => 'TLPG',
                '/\bBbm\b/i' => 'BBM',
                '/\bAmt\b/i' => 'AMT',
                '/\bRtc\b/i' => 'RTC',
                '/\bGps\b/i' => 'GPS',
                '/\bPc\b/i' => 'PC',
                '/\bAc\b/i' => 'AC',
            ];

            foreach (
                $acronyms
                as $pattern => $replacement
            ) {
                $result = preg_replace(
                    $pattern,
                    $replacement,
                    $result
                );
            }

            return trim($result);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Status Errorlog
        |--------------------------------------------------------------------------
        */
        private function applyErrorlogStatusFilter(
            $query,
            string $selectedStatus
        ): void {
            /*
            * event_status menjadi sumber utama.
            * follow_up_status digunakan jika event_status kosong.
            *
            * Nilai "-" juga dianggap kosong.
            */
            $statusSql = "
                LOWER(
                    COALESCE(
                        NULLIF(
                            NULLIF(
                                TRIM(event_status),
                                ''
                            ),
                            '-'
                        ),
                        NULLIF(
                            NULLIF(
                                TRIM(follow_up_status),
                                ''
                            ),
                            '-'
                        ),
                        ''
                    )
                )
            ";

            /*
            * Kata-kata yang menunjukkan laporan telah selesai.
            */
            $closedTerms = [
                'closed',
                'close',
                'resolved',
                'selesai',
                'done',
                'completed',
                'complete',
                'fixed',
                'solved',
            ];

            /*
            * Kata-kata yang menunjukkan laporan masih aktif.
            *
            * Status seperti "belum closed" harus tetap dianggap aktif.
            */
            $activeTerms = [
                'open',
                'pending',
                'waiting',
                'progress',
                'in progress',
                'on progress',
                'masih aktif',
                'masih terkendala',
                'belum closed',
                'belum close',
                'belum selesai',
                'not closed',
                'not resolved',
                'waiting for customer',
            ];

            $closedSql = implode(
                ' OR ',
                array_fill(
                    0,
                    count($closedTerms),
                    "{$statusSql} LIKE ?"
                )
            );

            $activeSql = implode(
                ' OR ',
                array_fill(
                    0,
                    count($activeTerms),
                    "{$statusSql} LIKE ?"
                )
            );

            $closedParams = array_map(
                fn (string $term) =>
                    '%' . $term . '%',
                $closedTerms
            );

            $activeParams = array_map(
                fn (string $term) =>
                    '%' . $term . '%',
                $activeTerms
            );

            /*
            |--------------------------------------------------------------------------
            | CLOSED
            |--------------------------------------------------------------------------
            |
            | Harus mengandung kata selesai dan tidak boleh mengandung
            | kata seperti "belum closed" atau "waiting".
            */
            if ($selectedStatus === 'closed') {
                $query
                    ->whereRaw(
                        "{$statusSql} <> ''"
                    )
                    ->whereRaw(
                        '(' . $closedSql . ')',
                        $closedParams
                    )
                    ->whereRaw(
                        'NOT (' . $activeSql . ')',
                        $activeParams
                    );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | MASIH AKTIF
            |--------------------------------------------------------------------------
            |
            | Status dianggap aktif jika:
            | - mengandung Open/Pending/Waiting/Progress; atau
            | - status terisi tetapi tidak memiliki kata Closed/Resolved/Selesai.
            */
            if ($selectedStatus === 'aktif') {
                $query
                    ->whereRaw(
                        "{$statusSql} <> ''"
                    )
                    ->whereRaw(
                        '((' .
                            $activeSql .
                        ') OR NOT (' .
                            $closedSql .
                        '))',
                        array_merge(
                            $activeParams,
                            $closedParams
                        )
                    );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | BELUM ADA STATUS
            |--------------------------------------------------------------------------
            */
            if ($selectedStatus === 'kosong') {
                $query->whereRaw(
                    "{$statusSql} = ''"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Menentukan Status Monitoring Errorlog
        |--------------------------------------------------------------------------
        */
        private function resolveErrorlogMonitoringStatus(
            ?string $eventStatus,
            ?string $followUpStatus
        ): string {
            $primaryStatus = trim(
                (string) $eventStatus
            );

            $secondaryStatus = trim(
                (string) $followUpStatus
            );

            $status = $primaryStatus !== ''
                && $primaryStatus !== '-'
                    ? $primaryStatus
                    : $secondaryStatus;

            $status = trim($status);

            if (
                $status === '' ||
                $status === '-'
            ) {
                return 'kosong';
            }

            $status = strtolower(
                Str::ascii($status)
            );

            $activeTerms = [
                'open',
                'pending',
                'waiting',
                'progress',
                'in progress',
                'on progress',
                'masih aktif',
                'masih terkendala',
                'belum closed',
                'belum close',
                'belum selesai',
                'not closed',
                'not resolved',
                'waiting for customer',
            ];

            foreach ($activeTerms as $term) {
                if (str_contains($status, $term)) {
                    return 'aktif';
                }
            }

            $closedTerms = [
                'closed',
                'close',
                'resolved',
                'selesai',
                'done',
                'completed',
                'complete',
                'fixed',
                'solved',
            ];

            foreach ($closedTerms as $term) {
                if (str_contains($status, $term)) {
                    return 'closed';
                }
            }

            /*
            * Status terisi tetapi tidak menunjukkan penyelesaian,
            * sehingga masih dianggap membutuhkan tindak lanjut.
            */
            return 'aktif';
        }

        /*
|--------------------------------------------------------------------------
| Membuat ringkasan visual halaman detail
|--------------------------------------------------------------------------
*/
private function buildDetailSummary(
    $query,
    string $jenis,
    Request $request
): array {
    /*
     * Nama file PDF dibutuhkan untuk proses klasifikasi
     * Pelanggaran dan Kendala.
     */
    $rows = $query
        ->with([
            'reportUpload:id,nama_file',
        ])
        ->get();

    $total = $rows->count();

    /*
    |--------------------------------------------------------------------------
    | Ringkasan TLPG
    |--------------------------------------------------------------------------
    */
    $tlpgCounts = $rows
        ->map(function ($row) {
            $tlpg = trim(
                (string) $row->tlpg
            );

            if ($tlpg === '') {
                return null;
            }

            return strtoupper($tlpg);
        })
        ->filter()
        ->countBy()
        ->sortDesc();

    $topTlpg = $tlpgCounts->keys()->first();
    $topTlpgCount = $topTlpg
        ? (int) $tlpgCounts->get($topTlpg, 0)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Jenis kejadian yang telah dinormalisasi
    |--------------------------------------------------------------------------
    */
    $eventLabels = $rows
        ->map(function ($row) use ($jenis) {
            /*
             * Pelanggaran mengikuti 22 kategori Form K3.2.
             */
            if ($jenis === 'pelanggaran') {
                return $this->classifyPelanggaranEvent(
                    $row
                ) ?: 'Belum Terklasifikasi';
            }

            /*
             * Kendala mengikuti hasil sterilisasi kategori.
             */
            if ($jenis === 'kendala') {
                return $this->classifyKendalaEvent(
                    $row
                ) ?: 'Kendala Lainnya';
            }

            /*
             * Accident dikelompokkan menjadi aktif dan pasif.
             */
            if ($jenis === 'accident') {
                $text = strtoupper(
                    trim(
                        implode(' ', [
                            (string) $row->category,
                            (string) $row->event_name,
                        ])
                    )
                );

                if (
                    str_contains($text, 'AKTIF')
                ) {
                    return 'Aktif';
                }

                if (
                    str_contains($text, 'PASIF')
                ) {
                    return 'Pasif';
                }

                return 'Belum Terklasifikasi';
            }

            /*
             * Errorlog menggunakan jenis error.
             */
            $eventName = trim(
                (string) $row->event_name
            );

            if ($eventName === '') {
                return 'Tidak Diketahui';
            }

            return Str::title(
                Str::lower($eventName)
            );
        })
        ->filter();

    $eventCounts = $eventLabels
        ->countBy()
        ->sortDesc();

    $topEvent = $eventCounts
        ->keys()
        ->first();

    $topEventCount = $topEvent
        ? (int) $eventCounts->get(
            $topEvent,
            0
        )
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Data mini grafik
    |--------------------------------------------------------------------------
    */
    $chartMode = 'horizontal';
    $chartTitle = 'Jenis Data Terbanyak';
    $chartCounts = $eventCounts;

    /*
     * Accident lebih nyaman dilihat sebagai komposisi.
     */
    if ($jenis === 'accident') {
        $chartMode = 'doughnut';
        $chartTitle = 'Komposisi Accident';
        $chartCounts = $eventCounts;
    }

    /*
     * Errorlog tanpa filter status:
     * tampilkan komposisi Closed, Aktif, dan Kosong.
     *
     * Jika filter status sudah dipilih:
     * tampilkan jenis error terbanyak dari status tersebut.
     */
    if ($jenis === 'errorlog') {
        if (
            !$request->filled(
                'status_errorlog'
            )
        ) {
            $statusLabels = $rows
                ->map(function ($row) {
                    $status =
                        $this->resolveErrorlogMonitoringStatus(
                            $row->event_status,
                            $row->follow_up_status
                        );

                    return match ($status) {
                        'closed' => 'Closed',
                        'aktif' => 'Masih Aktif',
                        default => 'Belum Ada Status',
                    };
                });

            $chartCounts = $statusLabels
                ->countBy()
                ->sortDesc();

            $chartMode = 'doughnut';
            $chartTitle =
                'Komposisi Status Errorlog';
        } else {
            $chartMode = 'horizontal';
            $chartTitle =
                'Jenis Errorlog Terbanyak';
            $chartCounts = $eventCounts;
        }
    }

    /*
     * Grafik bar hanya menampilkan lima kategori terbesar.
     */
    if ($chartMode === 'horizontal') {
        $chartCounts = $chartCounts
            ->take(5);
    }

    return [
        'total' => $total,

        'top_event' =>
            $topEvent ?: '-',

        'top_event_count' =>
            $topEventCount,

        'top_tlpg' =>
            $topTlpg ?: '-',

        'top_tlpg_count' =>
            $topTlpgCount,

        'chart_mode' =>
            $chartMode,

        'chart_title' =>
            $chartTitle,

        'chart_labels' =>
            $chartCounts
                ->keys()
                ->values()
                ->all(),

        'chart_values' =>
            $chartCounts
                ->values()
                ->map(
                    fn ($value) =>
                        (int) $value
                )
                ->all(),
    ];
}
}