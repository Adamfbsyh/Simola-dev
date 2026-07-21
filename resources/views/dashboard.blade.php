<x-app-layout>
    @php
        $currentUser = auth()->user();

        $categoryChartLabel =
            $categoryChartLabel
            ?? 'bulan data terbaru';

        /*
        |--------------------------------------------------------------------------
        | Permission data monitoring
        |--------------------------------------------------------------------------
        */

        $seriesPermissionMap = [
            'pelanggaran' => 'pelanggaran.view',
            'kendala' => 'kendala.view',
            'accident' => 'accident.view',
            'errorlog' => 'errorlog.view',
        ];

        $seriesLabelMap = [
            'pelanggaran' => 'Pelanggaran',
            'kendala' => 'Kendala',
            'accident' => 'Accident',
            'errorlog' => 'Errorlog',
        ];

        $allowedSeries = collect(
            $seriesPermissionMap
        )
            ->filter(
                fn ($permission) =>
                    $currentUser->can($permission)
            )
            ->keys()
            ->values()
            ->all();

        /*
         * Nilai dari controller atau query string tetap disaring
         * berdasarkan permission pengguna.
         */
        $requestedVisibleSeries =
            $visibleSeries
            ?? $allowedSeries;

        $requestedVisibleSeries =
            is_array($requestedVisibleSeries)
                ? $requestedVisibleSeries
                : $allowedSeries;

        $visibleSeries = array_values(
            array_unique(
                array_intersect(
                    $requestedVisibleSeries,
                    $allowedSeries
                )
            )
        );

        /*
         * Jika tidak ada pilihan valid tetapi pengguna memiliki
         * akses data, tampilkan semua series yang diizinkan.
         */
        if (
            empty($visibleSeries)
            &&
            !empty($allowedSeries)
        ) {
            $visibleSeries = $allowedSeries;
        }

        $trendMonthStart =
            $trendMonthStart
            ?? null;

        $trendMonthEnd =
            $trendMonthEnd
            ?? null;

        $compareCurrentMonth =
            $compareCurrentMonth
            ?? null;

        $comparePreviousMonth =
            $comparePreviousMonth
            ?? null;

        $trendPeriod =
            $trendPeriod
            ?? 'bulanan';

        $comparisonCards =
            $comparisonCards
            ?? [];

        $trenBulanan =
            $trenBulanan
            ?? collect();

        $pelanggaranChart =
            $pelanggaranChart
            ?? collect();

        $kendalaChart =
            $kendalaChart
            ?? collect();

        $accidentChart =
            $accidentChart
            ?? collect();

        $errorlogChart =
            $errorlogChart
            ?? collect();

        $skorPengemudiChart =
            $skorPengemudiChart
            ?? collect();

        /*
        |--------------------------------------------------------------------------
        | Nama bulan
        |--------------------------------------------------------------------------
        */

        $bulanMap = [
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
        ];

        $formatMonthInput = function (
            $value
        ) use (
            $bulanMap
        ) {
            if (!$value) {
                return null;
            }

            try {
                $date =
                    \Carbon\Carbon::createFromFormat(
                        'Y-m',
                        $value
                    );

                return
                    $bulanMap[
                        (int) $date->format('n')
                    ]
                    . ' '
                    . $date->format('Y');
            } catch (\Throwable $e) {
                return $value;
            }
        };

        $seriesSummary = collect(
            $visibleSeries
        )
            ->map(
                fn ($item) =>
                    $seriesLabelMap[$item]
                    ?? $item
            )
            ->implode(', ');

        if (
            $trendMonthStart
            &&
            $trendMonthEnd
        ) {
            $trendRangeSummary =
                $formatMonthInput(
                    $trendMonthStart
                )
                . ' - '
                . $formatMonthInput(
                    $trendMonthEnd
                );
        } elseif (
            $trendMonthStart
            &&
            !$trendMonthEnd
        ) {
            $trendRangeSummary =
                'Mulai '
                . $formatMonthInput(
                    $trendMonthStart
                )
                . ' - data terbaru';
        } elseif (
            !$trendMonthStart
            &&
            $trendMonthEnd
        ) {
            $trendRangeSummary =
                'Awal data - '
                . $formatMonthInput(
                    $trendMonthEnd
                );
        } else {
            $trendRangeSummary =
                'Otomatis semua data';
        }

        $compareSummary =
            (
                $compareCurrentMonth
                &&
                $comparePreviousMonth
            )
                ? $formatMonthInput(
                    $compareCurrentMonth
                )
                    . ' vs '
                    . $formatMonthInput(
                        $comparePreviousMonth
                    )
                : 'Otomatis';

        /*
        |--------------------------------------------------------------------------
        | Filter kartu perbandingan
        |--------------------------------------------------------------------------
        */

        $comparisonPermissionMap = [
            'Pelanggaran' =>
                'pelanggaran.view',

            'Kendala' =>
                'kendala.view',

            'Accident' =>
                'accident.view',

            'Errorlog' =>
                'errorlog.view',
        ];

        $visibleComparisonCards = collect(
            $comparisonCards
        )
            ->filter(
                function ($card) use (
                    $comparisonPermissionMap,
                    $currentUser
                ) {
                    $label =
                        $card['label']
                        ?? '';

                    $permission =
                        $comparisonPermissionMap[
                            $label
                        ]
                        ?? null;

                    return
                        $permission
                        &&
                        $currentUser->can(
                            $permission
                        );
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Filter data grafik perbandingan
        |--------------------------------------------------------------------------
        */

        $safeComparisonTrendData =
            $comparisonTrendData
            ?? null;

        if (
            is_array(
                $safeComparisonTrendData
            )
            &&
            isset(
                $safeComparisonTrendData[
                    'datasets'
                ]
            )
        ) {
            $allowedLabels = collect(
                $allowedSeries
            )
                ->map(
                    fn ($series) =>
                        $seriesLabelMap[
                            $series
                        ]
                        ?? $series
                )
                ->values();

            $safeComparisonTrendData[
                'datasets'
            ] = collect(
                $safeComparisonTrendData[
                    'datasets'
                ]
            )
                ->filter(
                    function (
                        $dataset
                    ) use (
                        $allowedLabels
                    ) {
                        $label = (string) (
                            $dataset['label']
                            ?? ''
                        );

                        return $allowedLabels
                            ->contains(
                                fn ($allowedLabel) =>
                                    str_starts_with(
                                        $label,
                                        $allowedLabel
                                    )
                            );
                    }
                )
                ->values()
                ->all();
        }

        $hasMonitoringAccess =
            count($allowedSeries) > 0;

        $hasCategoryChartAccess =
            $currentUser->can(
                'pelanggaran.view'
            )
            ||
            $currentUser->can(
                'kendala.view'
            )
            ||
            $currentUser->can(
                'accident.view'
            )
            ||
            $currentUser->can(
                'errorlog.view'
            )
            ||
            $currentUser->can(
                'driver-score.view'
            );

                    /*
        |--------------------------------------------------------------------------
        | Payload grafik yang aman berdasarkan permission
        |--------------------------------------------------------------------------
        */

        $dashboardChartPayload = [
            'trend' => [
                'labels' => collect($trenBulanan)
                    ->pluck('month')
                    ->values()
                    ->all(),

                'period' => (string) $trendPeriod,

                'visibleSeries' => array_values(
                    $visibleSeries
                ),

                'comparisonMode' => (bool) (
                    $comparisonTrendMode
                    ?? false
                ),

                'comparisonData' =>
                    $safeComparisonTrendData,

                'series' => [],
            ],

            'categoryCharts' => [],
        ];

        foreach (
            $seriesLabelMap
            as $seriesKey => $seriesLabel
        ) {
            if (
                !in_array(
                    $seriesKey,
                    $allowedSeries,
                    true
                )
            ) {
                continue;
            }

            $dashboardChartPayload[
                'trend'
            ]['series'][$seriesKey] = [
                'label' => $seriesLabel,

                'data' => collect($trenBulanan)
                    ->pluck($seriesKey)
                    ->map(
                        fn ($value) =>
                            (int) $value
                    )
                    ->values()
                    ->all(),
            ];
        }

        if (
            $currentUser->can(
                'pelanggaran.view'
            )
        ) {
            $dashboardChartPayload[
                'categoryCharts'
            ]['pelanggaran'] = [
                'selectId' =>
                    'pelanggaranType',

                'canvasId' =>
                    'pelanggaranChart',

                'defaultType' =>
                    'horizontalBar',

                'labels' =>
                    collect($pelanggaranChart)
                        ->pluck('event_name')
                        ->values()
                        ->all(),

                'values' =>
                    collect($pelanggaranChart)
                        ->pluck('total')
                        ->map(
                            fn ($value) =>
                                (int) $value
                        )
                        ->values()
                        ->all(),

                'label' =>
                    'Jumlah Pelanggaran',
            ];
        }

        if (
            $currentUser->can(
                'kendala.view'
            )
        ) {
            $dashboardChartPayload[
                'categoryCharts'
            ]['kendala'] = [
                'selectId' =>
                    'kendalaType',

                'canvasId' =>
                    'kendalaChart',

                'defaultType' =>
                    'horizontalBar',

                'labels' =>
                    collect($kendalaChart)
                        ->pluck('event_name')
                        ->values()
                        ->all(),

                'values' =>
                    collect($kendalaChart)
                        ->pluck('total')
                        ->map(
                            fn ($value) =>
                                (int) $value
                        )
                        ->values()
                        ->all(),

                'label' =>
                    'Jumlah Kendala',
            ];
        }

        if (
            $currentUser->can(
                'accident.view'
            )
        ) {
            $dashboardChartPayload[
                'categoryCharts'
            ]['accident'] = [
                'selectId' =>
                    'accidentType',

                'canvasId' =>
                    'accidentChart',

                'defaultType' =>
                    'doughnut',

                'labels' =>
                    collect($accidentChart)
                        ->pluck('category')
                        ->values()
                        ->all(),

                'values' =>
                    collect($accidentChart)
                        ->pluck('total')
                        ->map(
                            fn ($value) =>
                                (int) $value
                        )
                        ->values()
                        ->all(),

                'label' =>
                    'Jumlah Accident',
            ];
        }

        if (
            $currentUser->can(
                'errorlog.view'
            )
        ) {
            $dashboardChartPayload[
                'categoryCharts'
            ]['errorlog'] = [
                'selectId' =>
                    'errorlogType',

                'canvasId' =>
                    'errorlogChart',

                'defaultType' =>
                    'horizontalBar',

                'labels' =>
                    collect($errorlogChart)
                        ->pluck('event_name')
                        ->values()
                        ->all(),

                'values' =>
                    collect($errorlogChart)
                        ->pluck('total')
                        ->map(
                            fn ($value) =>
                                (int) $value
                        )
                        ->values()
                        ->all(),

                'label' =>
                    'Jumlah Errorlog',
            ];
        }

        if (
            $currentUser->can(
                'driver-score.view'
            )
        ) {
            $dashboardChartPayload[
                'categoryCharts'
            ]['skor'] = [
                'selectId' =>
                    'skorType',

                'canvasId' =>
                    'skorChart',

                'defaultType' =>
                    'horizontalBar',

                'labels' =>
                    collect($skorPengemudiChart)
                        ->pluck('driver_name')
                        ->values()
                        ->all(),

                'values' =>
                    collect($skorPengemudiChart)
                        ->pluck('total_risiko')
                        ->map(
                            fn ($value) =>
                                (int) $value
                        )
                        ->values()
                        ->all(),

                'label' =>
                    'Total Risiko Pengemudi',
            ];
        }
    @endphp

    <x-slot name="header">
        <div class="page-header">
            <div>
                <h2>
                    Dashboard Monitoring Utama
                </h2>

                <p>
                    Ringkasan Pelanggaran, Kendala,
                    Accident, Skor Pengemudi, dan Errorlog.
                </p>
            </div>

            <div class="header-actions">
                @can('dashboard.settings')
                    <select
                        id="themeSelector"
                        class="theme-select"
                    >
                        <option value="blue">
                            Default Biru
                        </option>

                        <option value="dark">
                            Dark Mode
                        </option>

                        <option value="emerald">
                            Emerald
                        </option>

                        <option value="navy">
                            Corporate Navy
                        </option>

                        <option value="orange">
                            Warm Orange
                        </option>
                    </select>
                @endcan

                @can('upload.view')
                    <a
                        href="{{ route(
                            'upload-terpadu.index'
                        ) }}"
                        class="btn-primary"
                    >
                        Upload Terpadu
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <style>
        :root {
            --page-bg: #eef2ff;
            --card-bg: #ffffff;
            --card-soft: #f8fafc;
            --border-color: #c7d2fe;
            --text-main: #111827;
            --text-muted: #6b7280;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --shadow:
                0 8px 20px
                rgba(15, 23, 42, 0.07);
        }

        body.theme-dark {
            --page-bg: #0f172a;
            --card-bg: #111827;
            --card-soft: #1f2937;
            --border-color: #334155;
            --text-main: #f9fafb;
            --text-muted: #cbd5e1;
            --primary: #38bdf8;
            --primary-hover: #0284c7;
            --shadow:
                0 10px 25px
                rgba(0, 0, 0, 0.35);
        }

        body.theme-emerald {
            --page-bg: #ecfdf5;
            --card-bg: #ffffff;
            --card-soft: #d1fae5;
            --border-color: #a7f3d0;
            --text-main: #064e3b;
            --text-muted: #047857;
            --primary: #059669;
            --primary-hover: #047857;
            --shadow:
                0 8px 20px
                rgba(5, 150, 105, 0.12);
        }

        body.theme-navy {
            --page-bg: #eef2ff;
            --card-bg: #ffffff;
            --card-soft: #e0e7ff;
            --border-color: #c7d2fe;
            --text-main: #1e1b4b;
            --text-muted: #4338ca;
            --primary: #312e81;
            --primary-hover: #1e1b4b;
            --shadow:
                0 8px 20px
                rgba(49, 46, 129, 0.12);
        }

        body.theme-orange {
            --page-bg: #fff7ed;
            --card-bg: #ffffff;
            --card-soft: #ffedd5;
            --border-color: #fed7aa;
            --text-main: #7c2d12;
            --text-muted: #c2410c;
            --primary: #ea580c;
            --primary-hover: #c2410c;
            --shadow:
                0 8px 20px
                rgba(234, 88, 12, 0.12);
        }

        body,
        .min-h-screen {
            background:
                var(--page-bg)
                !important;
        }

        .dashboard-wrapper {
            max-width: 1250px;
            margin: 0 auto;
            padding: 24px;
            background: var(--page-bg);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
        }

        .page-header h2 {
            margin: 0;
            color: var(--text-main);
            font-size: 22px;
            font-weight: 800;
        }

        .page-header p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-primary {
            padding: 10px 16px;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .btn-primary:hover {
            background:
                var(--primary-hover);
        }

        .theme-select,
        .chart-type-select {
            padding: 9px 12px;
            border: 1px solid
                var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(190px, 1fr)
                );
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card,
        .chart-card,
        .trend-info-card,
        .graph-setting-card,
        .empty-dashboard-card {
            border: 1px solid
                var(--border-color);
            border-radius: 14px;
            background: var(--card-bg);
            box-shadow: var(--shadow);
        }

        .summary-card {
            padding: 20px;
            color: inherit;
            text-decoration: none;
            transition: 0.2s;
        }

        .summary-card:hover,
        .chart-card.clickable-card:hover {
            transform:
                translateY(-3px);

            box-shadow:
                0 12px 28px
                rgba(15, 23, 42, 0.12);
        }

        .summary-card p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .summary-card h3 {
            margin: 8px 0 0;
            color: var(--text-main);
            font-size: 30px;
            font-weight: 900;
        }

        .trend-info-card {
            margin-bottom: 22px;
            padding: 22px;
        }

        .trend-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .trend-info-card h3,
        .chart-card h3 {
            margin: 0 0 6px;
            color: var(--text-main);
            font-size: 18px;
            font-weight: 800;
        }

        .trend-info-card p,
        .chart-card p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(210px, 1fr)
                );
            gap: 14px;
        }

        .comparison-card {
            min-height: 135px;
            padding: 16px;
            border: 1px solid
                var(--border-color);
            border-radius: 12px;
            background: var(--card-soft);
        }

        .comparison-label {
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .comparison-main {
            color: var(--text-main);
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
        }

        .comparison-prev {
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .comparison-footer {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .change-badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .change-badge.turun {
            background: #dcfce7;
            color: #166534;
        }

        .change-badge.naik {
            background: #fee2e2;
            color: #991b1b;
        }

        .change-badge.stabil {
            background: #e0f2fe;
            color: #075985;
        }

        .change-badge.baru {
            background: #fef3c7;
            color: #92400e;
        }

        .status-text {
            font-size: 12px;
            font-weight: 700;
        }

        .status-text.turun {
            color: #166534;
        }

        .status-text.naik {
            color: #991b1b;
        }

        .status-text.stabil {
            color: #075985;
        }

        .status-text.baru {
            color: #92400e;
        }

        .graph-setting-card {
            margin-bottom: 22px;
            overflow: hidden;
        }

        .graph-setting-card summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            cursor: pointer;
            list-style: none;
        }

        .graph-setting-card summary::-webkit-details-marker {
            display: none;
        }

        .graph-setting-title strong {
            display: block;
            margin-bottom: 4px;
            color: var(--text-main);
            font-size: 15px;
        }

        .graph-setting-title span {
            color: var(--text-muted);
            font-size: 12px;
        }

        .graph-setting-summary {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .graph-pill {
            padding: 7px 10px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .graph-open-btn {
            padding: 8px 12px;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .graph-setting-body {
            padding: 16px 18px 18px;
            border-top: 1px solid
                var(--border-color);
            background: var(--card-soft);
        }

        .graph-setting-form {
            display: grid;
            gap: 14px;
        }

        .setting-section {
            padding: 14px;
            border: 1px solid
                var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
        }

        .setting-section-title {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .setting-section-title h4 {
            margin: 0;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 900;
        }

        .setting-section-title p {
            margin: 3px 0 0;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .setting-grid {
            display: grid;
            gap: 12px;
        }

        .setting-grid.two {
            grid-template-columns:
                repeat(2, 1fr);
        }

        .setting-field label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
        }

        .setting-input {
            width: 100%;
            padding: 10px;
            border: 1px solid
                var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-main);
            font-size: 13px;
        }

        .series-check-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(170px, 1fr)
                );
            gap: 10px;
        }

        .series-check-item {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 10px;
            border: 1px solid
                var(--border-color);
            border-radius: 9px;
            background: var(--card-soft);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .setting-note {
            margin-top: 12px;
            padding: 11px 13px;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            background: #eef2ff;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.6;
        }

        .setting-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .setting-apply-btn {
            padding: 11px 18px;
            border: 0;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            font-weight: 900;
            cursor: pointer;
        }

        .setting-reset-btn {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 8px;
            background: #6b7280;
            color: #ffffff;
            font-weight: 900;
            text-decoration: none;
        }

        .chart-grid {
            display: grid;
            grid-template-columns:
                repeat(2, 1fr);
            gap: 22px;
        }

        .chart-card {
            padding: 22px;
            color: inherit;
            text-decoration: none;
            transition: 0.2s;
        }

        .chart-card.full {
            grid-column: span 2;
        }

        .chart-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .chart-card-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .chart-box {
            position: relative;
            height: 300px;
        }

        .chart-box.trend-box {
            height: 360px;
        }

        .clickable-card {
            cursor: pointer;
        }

        .empty-dashboard-card {
            padding: 28px;
            color: var(--text-muted);
            text-align: center;
        }

        .empty-dashboard-card strong {
            display: block;
            margin-bottom: 7px;
            color: var(--text-main);
            font-size: 16px;
        }

        body.theme-dark nav,
        body.theme-dark header {
            border-color: #334155 !important;
            background: #111827 !important;
        }

        @media (max-width: 1100px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }

            .chart-card.full {
                grid-column: span 1;
            }
        }

        @media (max-width: 800px) {
            .page-header,
            .chart-card-header,
            .graph-setting-card summary {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .graph-setting-summary {
                justify-content: flex-start;
            }

            .setting-grid.two {
                grid-template-columns: 1fr;
            }

            .setting-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }
    </style>

    <div class="dashboard-wrapper">
        @if($hasCategoryChartAccess)
            <div class="summary-grid">
                @can('pelanggaran.view')
                    <a
                        href="{{ route(
                            'monitoring.detail',
                            'pelanggaran'
                        ) }}"
                        class="summary-card"
                    >
                        <p>Pelanggaran</p>

                        <h3>
                            {{ $totalPelanggaran ?? 0 }}
                        </h3>
                    </a>
                @endcan

                @can('kendala.view')
                    <a
                        href="{{ route(
                            'monitoring.detail',
                            'kendala'
                        ) }}"
                        class="summary-card"
                    >
                        <p>Kendala</p>

                        <h3>
                            {{ $totalKendala ?? 0 }}
                        </h3>
                    </a>
                @endcan

                @can('accident.view')
                    <a
                        href="{{ route(
                            'monitoring.detail',
                            'accident'
                        ) }}"
                        class="summary-card"
                    >
                        <p>Accident</p>

                        <h3>
                            {{ $totalAccident ?? 0 }}
                        </h3>
                    </a>
                @endcan

                @can('driver-score.view')
                    <a
                        href="{{ route(
                            'monitoring.detail',
                            'skor-pengemudi'
                        ) }}"
                        class="summary-card"
                    >
                        <p>Pengemudi Dinilai</p>

                        <h3>
                            {{ $totalPengemudi ?? 0 }}
                        </h3>
                    </a>
                @endcan

                @can('errorlog.view')
                    <a
                        href="{{ route(
                            'monitoring.detail',
                            'errorlog'
                        ) }}"
                        class="summary-card"
                    >
                        <p>Errorlog</p>

                        <h3>
                            {{ $totalErrorlog ?? 0 }}
                        </h3>
                    </a>
                @endcan
            </div>
        @else
            <div class="empty-dashboard-card">
                <strong>
                    Belum ada fitur monitoring
                    yang diizinkan.
                </strong>

                Hubungi developer untuk mendapatkan
                akses fitur dashboard.
            </div>
        @endif

        @if($visibleComparisonCards->isNotEmpty())
            <div class="trend-info-card">
                <div class="trend-header">
                    <div>
                        <h3>
                            {{
                                $comparisonTitle
                                ?? 'Perbandingan Data'
                            }}
                        </h3>

                        <p>
                            {{
                                $comparisonSubtitle
                                ?? 'Perbandingan periode monitoring.'
                            }}
                        </p>
                    </div>
                </div>

                <div class="comparison-grid">
                    @foreach(
                        $visibleComparisonCards
                        as $card
                    )
                        <div class="comparison-card">
                            <div class="comparison-label">
                                {{
                                    $card['label']
                                    ?? '-'
                                }}
                            </div>

                            <div class="comparison-main">
                                {{
                                    $card['current']
                                    ?? 0
                                }}
                            </div>

                            <div class="comparison-prev">
                                Sebelumnya:

                                <b>
                                    {{
                                        $card['previous']
                                        ?? 0
                                    }}
                                </b>
                            </div>

                            <div class="comparison-footer">
                                <span
                                    class="change-badge {{
                                        $card['direction']
                                        ?? 'stabil'
                                    }}"
                                >
                                    @if(
                                        (
                                            $card['percentage']
                                            ?? null
                                        ) === null
                                    )
                                        {{
                                            $card['status']
                                            ?? '-'
                                        }}
                                    @elseif(
                                        (
                                            $card['direction']
                                            ?? ''
                                        ) === 'turun'
                                    )
                                        ↓ {{
                                            abs(
                                                $card[
                                                    'percentage'
                                                ]
                                            )
                                        }}%
                                    @elseif(
                                        (
                                            $card['direction']
                                            ?? ''
                                        ) === 'naik'
                                    )
                                        ↑ {{
                                            abs(
                                                $card[
                                                    'percentage'
                                                ]
                                            )
                                        }}%
                                    @else
                                        → {{
                                            abs(
                                                $card[
                                                    'percentage'
                                                ]
                                            )
                                        }}%
                                    @endif
                                </span>

                                <span
                                    class="status-text {{
                                        $card['direction']
                                        ?? 'stabil'
                                    }}"
                                >
                                    {{
                                        $card['status']
                                        ?? '-'
                                    }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @can('dashboard.settings')
            <details class="graph-setting-card">
                <summary>
                    <div class="graph-setting-title">
                        <strong>
                            Pengaturan Dashboard
                        </strong>

                        <span>
                            Atur data yang tampil,
                            rentang grafik, dan perbandingan.
                        </span>
                    </div>

                    <div class="graph-setting-summary">
                        <span class="graph-pill">
                            {{
                                $seriesSummary
                                ?: 'Tidak Ada Data'
                            }}
                        </span>

                        <span class="graph-pill">
                            Grafik:
                            {{ $trendRangeSummary }}
                        </span>

                        <span class="graph-pill">
                            Banding:
                            {{ $compareSummary }}
                        </span>

                        <span class="graph-open-btn">
                            Buka Pengaturan
                        </span>
                    </div>
                </summary>

                <div class="graph-setting-body">
                    <form
                        method="GET"
                        action="{{ route('dashboard') }}"
                        class="graph-setting-form"
                    >
                        <input
                            type="hidden"
                            name="trend_period"
                            value="{{ $trendPeriod }}"
                        >

                        <div class="setting-section">
                            <div class="setting-section-title">
                                <div>
                                    <h4>
                                        1. Data yang Ditampilkan
                                    </h4>

                                    <p>
                                        Hanya data yang diizinkan
                                        untuk akun ini yang dapat dipilih.
                                    </p>
                                </div>
                            </div>

                            <div class="series-check-grid">
                                @can('pelanggaran.view')
                                    <label class="series-check-item">
                                        <input
                                            type="checkbox"
                                            name="series[]"
                                            value="pelanggaran"
                                            @checked(
                                                in_array(
                                                    'pelanggaran',
                                                    $visibleSeries,
                                                    true
                                                )
                                            )
                                        >

                                        Pelanggaran
                                    </label>
                                @endcan

                                @can('kendala.view')
                                    <label class="series-check-item">
                                        <input
                                            type="checkbox"
                                            name="series[]"
                                            value="kendala"
                                            @checked(
                                                in_array(
                                                    'kendala',
                                                    $visibleSeries,
                                                    true
                                                )
                                            )
                                        >

                                        Kendala
                                    </label>
                                @endcan

                                @can('accident.view')
                                    <label class="series-check-item">
                                        <input
                                            type="checkbox"
                                            name="series[]"
                                            value="accident"
                                            @checked(
                                                in_array(
                                                    'accident',
                                                    $visibleSeries,
                                                    true
                                                )
                                            )
                                        >

                                        Accident
                                    </label>
                                @endcan

                                @can('errorlog.view')
                                    <label class="series-check-item">
                                        <input
                                            type="checkbox"
                                            name="series[]"
                                            value="errorlog"
                                            @checked(
                                                in_array(
                                                    'errorlog',
                                                    $visibleSeries,
                                                    true
                                                )
                                            )
                                        >

                                        Errorlog
                                    </label>
                                @endcan
                            </div>
                        </div>

                        <div class="setting-section">
                            <div class="setting-section-title">
                                <div>
                                    <h4>
                                        2. Rentang Grafik Tren
                                    </h4>

                                    <p>
                                        Mengatur periode awal dan akhir
                                        data pada grafik tren.
                                    </p>
                                </div>
                            </div>

                            <div class="setting-grid two">
                                <div class="setting-field">
                                    <label>
                                        Mulai Bulan
                                    </label>

                                    <input
                                        type="month"
                                        name="trend_month_start"
                                        value="{{ $trendMonthStart }}"
                                        class="setting-input"
                                    >
                                </div>

                                <div class="setting-field">
                                    <label>
                                        Sampai Bulan
                                    </label>

                                    <input
                                        type="month"
                                        name="trend_month_end"
                                        value="{{ $trendMonthEnd }}"
                                        class="setting-input"
                                    >
                                </div>
                            </div>

                            <div class="setting-note">
                                Kosongkan keduanya agar sistem
                                menampilkan seluruh periode data
                                yang tersedia.
                            </div>
                        </div>

                        <div class="setting-section">
                            <div class="setting-section-title">
                                <div>
                                    <h4>
                                        3. Perbandingan Bulan
                                    </h4>

                                    <p>
                                        Mengatur kartu perbandingan dan
                                        grafik dua bulan.
                                    </p>
                                </div>
                            </div>

                            <div class="setting-grid two">
                                <div class="setting-field">
                                    <label>
                                        Bulan yang Dicek
                                    </label>

                                    <input
                                        type="month"
                                        name="compare_current_month"
                                        value="{{ $compareCurrentMonth }}"
                                        class="setting-input"
                                    >
                                </div>

                                <div class="setting-field">
                                    <label>
                                        Bulan Pembanding
                                    </label>

                                    <input
                                        type="month"
                                        name="compare_previous_month"
                                        value="{{ $comparePreviousMonth }}"
                                        class="setting-input"
                                    >
                                </div>
                            </div>

                            <div class="setting-note">
                                Contoh: isi Juli 2026 sebagai
                                bulan yang dicek dan Juni 2026
                                sebagai bulan pembanding.
                            </div>
                        </div>

                        <div class="setting-actions">
                            <button
                                type="submit"
                                class="setting-apply-btn"
                            >
                                Terapkan
                            </button>

                            <a
                                href="{{ route('dashboard') }}"
                                class="setting-reset-btn"
                            >
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </details>
        @endcan

        @if($hasCategoryChartAccess)
            <div class="chart-grid">
                @if($hasMonitoringAccess)
                    <div class="chart-card full">
                        <div class="chart-card-header">
                            <div>
                                <h3>
                                    {{
                                        $trendTitle
                                        ?? 'Tren Monitoring'
                                    }}
                                </h3>

                                <p>
                                    {{
                                        $trendSubtitle
                                        ?? 'Perkembangan data monitoring.'
                                    }}
                                </p>
                            </div>

                            <div class="chart-card-actions">
                                <select
                                    id="trendPeriod"
                                    class="chart-type-select"
                                >
                                    <option
                                        value="harian"
                                        @selected(
                                            $trendPeriod
                                            === 'harian'
                                        )
                                    >
                                        Harian
                                    </option>

                                    <option
                                        value="mingguan"
                                        @selected(
                                            $trendPeriod
                                            === 'mingguan'
                                        )
                                    >
                                        Mingguan
                                    </option>

                                    <option
                                        value="bulanan"
                                        @selected(
                                            $trendPeriod
                                            === 'bulanan'
                                        )
                                    >
                                        Bulanan
                                    </option>

                                    <option
                                        value="tahunan"
                                        @selected(
                                            $trendPeriod
                                            === 'tahunan'
                                        )
                                    >
                                        Tahunan
                                    </option>
                                </select>

                                <select
                                    id="trenType"
                                    class="chart-type-select"
                                >
                                    <option value="line">
                                        Line
                                    </option>

                                    <option value="area">
                                        Area
                                    </option>

                                    <option value="bar">
                                        Vertical Bar
                                    </option>

                                    <option value="stackedBar">
                                        Stacked Bar
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="chart-box trend-box">
                            <canvas
                                id="trenBulananChart"
                            ></canvas>
                        </div>
                    </div>
                @endif

                @can('pelanggaran.view')
                    <div
                        class="chart-card clickable-card"
                        onclick="window.location={{
                            \Illuminate\Support\Js::from(
                                route(
                                    'monitoring.detail',
                                    'pelanggaran'
                                )
                            )
                        }}"
                    >
                        <div class="chart-card-header">
                            <div>
                                <h3>
                                    Grafik Pelanggaran
                                </h3>

                                <p>
                                    Menampilkan jenis pelanggaran
                                    terbanyak pada
                                    {{ $categoryChartLabel }}.
                                </p>
                            </div>

                            <select
                                id="pelanggaranType"
                                class="chart-type-select"
                                onclick="event.stopPropagation()"
                            >
                                <option value="horizontalBar">
                                    Horizontal Bar
                                </option>

                                <option value="bar">
                                    Vertical Bar
                                </option>

                                <option value="line">
                                    Line
                                </option>

                                <option value="area">
                                    Area
                                </option>

                                <option value="steppedLine">
                                    Stepped Line
                                </option>

                                <option value="doughnut">
                                    Doughnut
                                </option>

                                <option value="pie">
                                    Pie
                                </option>

                                <option value="polarArea">
                                    Polar Area
                                </option>

                                <option value="radar">
                                    Radar
                                </option>
                            </select>
                        </div>

                        <div class="chart-box">
                            <canvas
                                id="pelanggaranChart"
                            ></canvas>
                        </div>
                    </div>
                @endcan

                @can('kendala.view')
                    <div
                        class="chart-card clickable-card"
                        onclick="window.location={{
                            \Illuminate\Support\Js::from(
                                route(
                                    'monitoring.detail',
                                    'kendala'
                                )
                            )
                        }}"
                    >
                        <div class="chart-card-header">
                            <div>
                                <h3>
                                    Grafik Kendala
                                </h3>

                                <p>
                                    Menampilkan jenis kendala terbanyak
                                    pada {{ $categoryChartLabel }}.
                                </p>
                            </div>

                            <select
                                id="kendalaType"
                                class="chart-type-select"
                                onclick="event.stopPropagation()"
                            >
                                <option value="horizontalBar">
                                    Horizontal Bar
                                </option>

                                <option value="bar">
                                    Vertical Bar
                                </option>

                                <option value="line">
                                    Line
                                </option>

                                <option value="area">
                                    Area
                                </option>

                                <option value="steppedLine">
                                    Stepped Line
                                </option>

                                <option value="doughnut">
                                    Doughnut
                                </option>

                                <option value="pie">
                                    Pie
                                </option>

                                <option value="polarArea">
                                    Polar Area
                                </option>

                                <option value="radar">
                                    Radar
                                </option>
                            </select>
                        </div>

                        <div class="chart-box">
                            <canvas
                                id="kendalaChart"
                            ></canvas>
                        </div>
                    </div>
                @endcan

                @can('accident.view')
                    <div
                        class="chart-card clickable-card"
                        onclick="window.location={{
                            \Illuminate\Support\Js::from(
                                route(
                                    'monitoring.detail',
                                    'accident'
                                )
                            )
                        }}"
                    >
                        <div class="chart-card-header">
                            <div>
                                <h3>
                                    Grafik Accident
                                </h3>

                                <p>
                                    Menampilkan komposisi accident
                                    pada {{ $categoryChartLabel }}.
                                </p>
                            </div>

                            <select
                                id="accidentType"
                                class="chart-type-select"
                                onclick="event.stopPropagation()"
                            >
                                <option value="doughnut">
                                    Doughnut
                                </option>

                                <option value="pie">
                                    Pie
                                </option>

                                <option value="polarArea">
                                    Polar Area
                                </option>

                                <option value="radar">
                                    Radar
                                </option>

                                <option value="horizontalBar">
                                    Horizontal Bar
                                </option>

                                <option value="bar">
                                    Vertical Bar
                                </option>

                                <option value="line">
                                    Line
                                </option>

                                <option value="area">
                                    Area
                                </option>
                            </select>
                        </div>

                        <div class="chart-box">
                            <canvas
                                id="accidentChart"
                            ></canvas>
                        </div>
                    </div>
                @endcan

                @can('errorlog.view')
                    <div
                        class="chart-card clickable-card"
                        onclick="window.location={{
                            \Illuminate\Support\Js::from(
                                route(
                                    'monitoring.detail',
                                    'errorlog'
                                )
                            )
                        }}"
                    >
                        <div class="chart-card-header">
                            <div>
                                <h3>
                                    Grafik Errorlog
                                </h3>

                                <p>
                                    Menampilkan jenis errorlog terbanyak
                                    pada {{ $categoryChartLabel }}.
                                </p>
                            </div>

                            <select
                                id="errorlogType"
                                class="chart-type-select"
                                onclick="event.stopPropagation()"
                            >
                                <option value="horizontalBar">
                                    Horizontal Bar
                                </option>

                                <option value="bar">
                                    Vertical Bar
                                </option>

                                <option value="line">
                                    Line
                                </option>

                                <option value="area">
                                    Area
                                </option>

                                <option value="steppedLine">
                                    Stepped Line
                                </option>

                                <option value="doughnut">
                                    Doughnut
                                </option>

                                <option value="pie">
                                    Pie
                                </option>

                                <option value="polarArea">
                                    Polar Area
                                </option>

                                <option value="radar">
                                    Radar
                                </option>
                            </select>
                        </div>

                        <div class="chart-box">
                            <canvas
                                id="errorlogChart"
                            ></canvas>
                        </div>
                    </div>
                @endcan

                @can('driver-score.view')
                    <div
                        class="chart-card full clickable-card"
                        onclick="window.location={{
                            \Illuminate\Support\Js::from(
                                route(
                                    'monitoring.detail',
                                    'skor-pengemudi'
                                )
                            )
                        }}"
                    >
                        <div class="chart-card-header">
                            <div>
                                <h3>
                                    Grafik Skor Pengemudi
                                </h3>

                                <p>
                                    Menampilkan nama AMT dengan
                                    total risiko tertinggi pada
                                    {{ $categoryChartLabel }}.
                                </p>
                            </div>

                            <select
                                id="skorType"
                                class="chart-type-select"
                                onclick="event.stopPropagation()"
                            >
                                <option value="horizontalBar">
                                    Horizontal Bar
                                </option>

                                <option value="bar">
                                    Vertical Bar
                                </option>

                                <option value="line">
                                    Line
                                </option>

                                <option value="area">
                                    Area
                                </option>

                                <option value="steppedLine">
                                    Stepped Line
                                </option>

                                <option value="radar">
                                    Radar
                                </option>

                                <option value="polarArea">
                                    Polar Area
                                </option>
                            </select>
                        </div>

                        <div class="chart-box">
                            <canvas
                                id="skorChart"
                            ></canvas>
                        </div>
                    </div>
                @endcan
            </div>
        @endif
    </div>

        <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"
    ></script>

    <script>
        window.addEventListener(
            'load',
            function () {
                if (
                    typeof window.Chart
                    === 'undefined'
                ) {
                    console.error(
                        'Chart.js gagal dimuat.'
                    );

                    return;
                }

                const dashboardChartPayload =
                    {{
                        \Illuminate\Support\Js::from(
                            $dashboardChartPayload
                        )
                    }};

                const chartInstances = {};

                const palette = [
                    '#3b82f6',
                    '#f43f5e',
                    '#fb923c',
                    '#fbbf24',
                    '#14b8a6',
                    '#8b5cf6',
                    '#06b6d4',
                    '#84cc16',
                    '#ec4899',
                    '#64748b'
                ];

                const seriesColors = {
                    pelanggaran: '#3b82f6',
                    kendala: '#fb7185',
                    accident: '#fb923c',
                    errorlog: '#fbbf24',
                };

                function destroyChart(
                    canvasId
                ) {
                    if (
                        chartInstances[
                            canvasId
                        ]
                    ) {
                        chartInstances[
                            canvasId
                        ].destroy();

                        delete chartInstances[
                            canvasId
                        ];
                    }
                }

                function colorWithAlpha(
                    color,
                    alpha
                ) {
                    const safeColor =
                        String(
                            color
                            || '#3b82f6'
                        );

                    if (
                        !safeColor.startsWith(
                            '#'
                        )
                    ) {
                        return safeColor;
                    }

                    let hex =
                        safeColor.substring(1);

                    if (
                        hex.length === 3
                    ) {
                        hex = hex
                            .split('')
                            .map(
                                character =>
                                    character
                                    + character
                            )
                            .join('');
                    }

                    if (
                        hex.length !== 6
                    ) {
                        return safeColor;
                    }

                    const red = parseInt(
                        hex.substring(0, 2),
                        16
                    );

                    const green = parseInt(
                        hex.substring(2, 4),
                        16
                    );

                    const blue = parseInt(
                        hex.substring(4, 6),
                        16
                    );

                    return (
                        'rgba('
                        + red
                        + ','
                        + green
                        + ','
                        + blue
                        + ','
                        + alpha
                        + ')'
                    );
                }

                function resolveChartType(
                    selectedType
                ) {
                    const result = {
                        type: selectedType,
                        indexAxis: 'x',
                        fill: false,
                        stepped: false,
                        stacked: false,
                    };

                    if (
                        selectedType
                        === 'horizontalBar'
                    ) {
                        result.type = 'bar';
                        result.indexAxis = 'y';
                    }

                    if (
                        selectedType
                        === 'area'
                    ) {
                        result.type = 'line';
                        result.fill = true;
                    }

                    if (
                        selectedType
                        === 'steppedLine'
                    ) {
                        result.type = 'line';
                        result.stepped = true;
                    }

                    if (
                        selectedType
                        === 'stackedBar'
                    ) {
                        result.type = 'bar';
                        result.stacked = true;
                    }

                    return result;
                }

                function renderCategoryChart(
                        chartKey,
                        config,
                        selectedType
                    ) {
                        const canvas =
                            document.getElementById(
                                config.canvasId
                            );

                        if (!canvas) {
                            return;
                        }

                        destroyChart(
                            config.canvasId
                        );

                        const typeConfig =
                            resolveChartType(
                                selectedType
                            );

                        const labels =
                            Array.isArray(
                                config.labels
                            )
                                ? config.labels
                                : [];

                        const values =
                            Array.isArray(
                                config.values
                            )
                                ? config.values.map(
                                    value =>
                                        Number(
                                            value || 0
                                        )
                                )
                                : [];

                        const circularTypes = [
                            'doughnut',
                            'pie',
                            'polarArea',
                        ];

                        const isCircular =
                            circularTypes.includes(
                                typeConfig.type
                            );

                        const mainColor =
                            palette[0];

                        const perItemColors = labels.map(
                            (label, index) =>
                                palette[index % palette.length]
                        );

                        const backgroundColor =
                            (
                                isCircular
                                || typeConfig.type === 'bar'
                                || typeConfig.type === 'radar'
                            )
                                ? perItemColors.map(color =>
                                    typeConfig.type === 'bar'
                                        ? colorWithAlpha(color, 0.55)
                                        : color
                                )
                                : typeConfig.fill
                                    ? colorWithAlpha(
                                        mainColor,
                                        0.2
                                    )
                                    : colorWithAlpha(
                                        mainColor,
                                        0.55
                                    );

                        const borderColor =
                            (
                                isCircular
                                || typeConfig.type === 'bar'
                                || typeConfig.type === 'radar'
                            )
                                ? perItemColors
                                : mainColor;

                        const options = {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis:
                                typeConfig.indexAxis,
                            plugins: {
                                legend: {
                                    display:
                                        isCircular
                                        || typeConfig.type === 'radar',
                                    position: 'top',
                                },
                            },
                        };

                        if (
                            typeConfig.type === 'bar'
                            || typeConfig.type === 'line'
                        ) {
                            options.scales = {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                    },
                                },
                            };
                        }

                        chartInstances[
                            config.canvasId
                        ] = new Chart(
                            canvas,
                            {
                                type: typeConfig.type,
                                data: {
                                    labels: labels,
                                    datasets: [
                                        {
                                            label: config.label,
                                            data: values,
                                            borderColor: borderColor,
                                            backgroundColor: backgroundColor,
                                            pointBackgroundColor:
                                                Array.isArray(borderColor)
                                                    ? borderColor[0]
                                                    : borderColor,
                                            pointBorderColor:
                                                Array.isArray(borderColor)
                                                    ? borderColor[0]
                                                    : borderColor,
                                            borderWidth: 2,
                                            borderRadius:
                                                typeConfig.type === 'bar'
                                                    ? 5
                                                    : 0,
                                            tension: 0.35,
                                            fill: typeConfig.fill,
                                            stepped:
                                                typeConfig.stepped,
                                        },
                                    ],
                                },
                                options: options,
                            }
                        );
                    }

                function initializeCategoryCharts() {
                    const configs =
                        dashboardChartPayload
                            .categoryCharts
                        || {};

                    Object.entries(
                        configs
                    ).forEach(
                        function (
                            [
                                chartKey,
                                config
                            ]
                        ) {
                            const select =
                                document.getElementById(
                                    config.selectId
                                );

                            const canvas =
                                document.getElementById(
                                    config.canvasId
                                );

                            if (
                                !select
                                ||
                                !canvas
                            ) {
                                return;
                            }

                            const storageKey =
                                'chart_type_'
                                + chartKey;

                            const savedType =
                                localStorage.getItem(
                                    storageKey
                                )
                                ||
                                config.defaultType;

                            select.value =
                                savedType;

                            renderCategoryChart(
                                chartKey,
                                config,
                                savedType
                            );

                            select.addEventListener(
                                'change',
                                function () {
                                    localStorage.setItem(
                                        storageKey,
                                        this.value
                                    );

                                    renderCategoryChart(
                                        chartKey,
                                        config,
                                        this.value
                                    );
                                }
                            );
                        }
                    );
                }

                function renderTrendChart(
                    selectedType
                ) {
                    const canvas =
                        document.getElementById(
                            'trenBulananChart'
                        );

                    if (!canvas) {
                        return;
                    }

                    destroyChart(
                        'trenBulananChart'
                    );

                    const trend =
                        dashboardChartPayload
                            .trend
                        || {};

                    const typeConfig =
                        resolveChartType(
                            selectedType
                        );

                    let labels =
                        Array.isArray(
                            trend.labels
                        )
                            ? trend.labels
                            : [];

                    let datasets = [];

                    const comparisonData =
                        trend.comparisonData;

                    if (
                        trend.comparisonMode
                        &&
                        comparisonData
                        &&
                        Array.isArray(
                            comparisonData.labels
                        )
                        &&
                        Array.isArray(
                            comparisonData.datasets
                        )
                    ) {
                        labels =
                            comparisonData.labels;

                        datasets =
                            comparisonData
                                .datasets
                                .map(
                                    function (
                                        dataset,
                                        index
                                    ) {
                                        const color =
                                            dataset.color
                                            ||
                                            palette[
                                                index
                                                %
                                                palette.length
                                            ];

                                        return {
                                            label:
                                                dataset.label,

                                            data:
                                                Array.isArray(
                                                    dataset.data
                                                )
                                                    ? dataset.data
                                                    : [],

                                            borderColor:
                                                color,

                                            backgroundColor:
                                                typeConfig.fill
                                                    ? colorWithAlpha(
                                                        color,
                                                        0.18
                                                    )
                                                    : colorWithAlpha(
                                                        color,
                                                        0.55
                                                    ),

                                            pointBackgroundColor:
                                                color,

                                            pointBorderColor:
                                                color,

                                            borderWidth: 3,

                                            borderRadius:
                                                typeConfig.type
                                                    === 'bar'
                                                    ? 5
                                                    : 0,

                                            tension: 0.35,

                                            fill:
                                                typeConfig.fill,

                                            stack:
                                                typeConfig.stacked
                                                    ? 'monitoring'
                                                    : undefined,
                                        };
                                    }
                                );
                    } else {
                        const visibleSeries =
                            Array.isArray(
                                trend.visibleSeries
                            )
                                ? trend.visibleSeries
                                : [];

                        const seriesData =
                            trend.series
                            || {};

                        datasets =
                            visibleSeries
                                .map(
                                    function (
                                        seriesKey,
                                        index
                                    ) {
                                        const series =
                                            seriesData[
                                                seriesKey
                                            ];

                                        if (!series) {
                                            return null;
                                        }

                                        const color =
                                            seriesColors[
                                                seriesKey
                                            ]
                                            ||
                                            palette[
                                                index
                                                %
                                                palette.length
                                            ];

                                        return {
                                            label:
                                                series.label,

                                            data:
                                                Array.isArray(
                                                    series.data
                                                )
                                                    ? series.data
                                                    : [],

                                            borderColor:
                                                color,

                                            backgroundColor:
                                                typeConfig.fill
                                                    ? colorWithAlpha(
                                                        color,
                                                        0.18
                                                    )
                                                    : colorWithAlpha(
                                                        color,
                                                        0.55
                                                    ),

                                            pointBackgroundColor:
                                                color,

                                            pointBorderColor:
                                                color,

                                            borderWidth: 3,

                                            borderRadius:
                                                typeConfig.type
                                                    === 'bar'
                                                    ? 5
                                                    : 0,

                                            tension: 0.35,

                                            fill:
                                                typeConfig.fill,

                                            stepped:
                                                typeConfig.stepped,

                                            stack:
                                                typeConfig.stacked
                                                    ? 'monitoring'
                                                    : undefined,
                                        };
                                    }
                                )
                                .filter(Boolean);
                    }

                    chartInstances[
                        'trenBulananChart'
                    ] = new Chart(
                        canvas,
                        {
                            type:
                                typeConfig.type,

                            data: {
                                labels: labels,
                                datasets: datasets,
                            },

                            options: {
                                responsive: true,

                                maintainAspectRatio:
                                    false,

                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },

                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                    },
                                },

                                scales: {
                                    x: {
                                        stacked:
                                            typeConfig.stacked,

                                        ticks: {
                                            maxRotation: 35,
                                            minRotation: 0,
                                        },
                                    },

                                    y: {
                                        stacked:
                                            typeConfig.stacked,

                                        beginAtZero: true,

                                        ticks: {
                                            precision: 0,
                                        },
                                    },
                                },
                            },
                        }
                    );
                }

                function initializeTrendChart() {
                    const select =
                        document.getElementById(
                            'trenType'
                        );

                    const canvas =
                        document.getElementById(
                            'trenBulananChart'
                        );

                    if (
                        !select
                        ||
                        !canvas
                    ) {
                        return;
                    }

                    const storageKey =
                        'chart_type_tren';

                    const savedType =
                        localStorage.getItem(
                            storageKey
                        )
                        ||
                        'line';

                    select.value =
                        savedType;

                    renderTrendChart(
                        savedType
                    );

                    select.addEventListener(
                        'change',
                        function () {
                            localStorage.setItem(
                                storageKey,
                                this.value
                            );

                            renderTrendChart(
                                this.value
                            );
                        }
                    );
                }

                function initializeTheme() {
                    const themeSelector =
                        document.getElementById(
                            'themeSelector'
                        );

                    const savedTheme =
                        localStorage.getItem(
                            'dashboard_theme'
                        )
                        ||
                        'blue';

                    function applyTheme(
                        theme
                    ) {
                        document.body
                            .classList
                            .remove(
                                'theme-blue',
                                'theme-dark',
                                'theme-emerald',
                                'theme-navy',
                                'theme-orange'
                            );

                        document.body
                            .classList
                            .add(
                                'theme-'
                                + theme
                            );

                        localStorage.setItem(
                            'dashboard_theme',
                            theme
                        );
                    }

                    applyTheme(
                        savedTheme
                    );

                    if (themeSelector) {
                        themeSelector.value =
                            savedTheme;

                        themeSelector
                            .addEventListener(
                                'change',
                                function () {
                                    applyTheme(
                                        this.value
                                    );
                                }
                            );
                    }
                }

                function initializeTrendPeriod() {
                    const select =
                        document.getElementById(
                            'trendPeriod'
                        );

                    if (!select) {
                        return;
                    }

                    select.addEventListener(
                        'change',
                        function () {
                            const url =
                                new URL(
                                    window.location.href
                                );

                            url.searchParams.set(
                                'trend_period',
                                this.value
                            );

                            url.searchParams.delete(
                                'series[]'
                            );

                            const visibleSeries =
                                dashboardChartPayload
                                    .trend
                                    .visibleSeries
                                || [];

                            visibleSeries.forEach(
                                function (
                                    series
                                ) {
                                    url.searchParams
                                        .append(
                                            'series[]',
                                            series
                                        );
                                }
                            );

                            [
                                'trend_month_start',
                                'trend_month_end',
                                'compare_current_month',
                                'compare_previous_month',
                            ].forEach(
                                function (
                                    fieldName
                                ) {
                                    const input =
                                        document.querySelector(
                                            '[name="'
                                            + fieldName
                                            + '"]'
                                        );

                                    if (
                                        input
                                        &&
                                        input.value
                                    ) {
                                        url.searchParams.set(
                                            fieldName,
                                            input.value
                                        );
                                    }
                                }
                            );

                            window.location.href =
                                url.toString();
                        }
                    );
                }

                try {
                    initializeTheme();
                    initializeTrendChart();
                    initializeCategoryCharts();
                    initializeTrendPeriod();
                } catch (error) {
                    console.error(
                        'Dashboard chart error:',
                        error
                    );
                }
            }
        );
    </script>
</x-app-layout>