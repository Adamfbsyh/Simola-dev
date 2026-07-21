<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail {{ ucfirst($jenis) }}
        </h2>
    </x-slot>

    <style>
        .detail-wrapper {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px;
        }

        .detail-card {
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .filter-grid {
            display: grid;
            grid-template-columns:
                minmax(180px, 2fr)
                repeat(5, minmax(135px, 1fr))
                auto
                auto;
            gap: 10px;
            margin-bottom: 14px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
        }

        .filter-control {
            width: 100%;
            min-height: 41px;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
        }

        .filter-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .btn-filter,
        .btn-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 41px;
            box-sizing: border-box;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .btn-filter {
            border: none;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
        }

        .btn-filter:hover {
            background: #1d4ed8;
        }

        .btn-reset {
            background: #6b7280;
            color: #ffffff;
            text-decoration: none;
        }

        .btn-reset:hover {
            background: #4b5563;
        }

        .filter-total-box {
            margin: 14px 0;
            padding: 12px 14px;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            background: #eef2ff;
            color: #1e3a8a;
            font-size: 13px;
            font-weight: 700;
        }

        .detail-alert-error {
            margin: 12px 0;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 13px;
            font-weight: 700;
        }

        .pdf-print-card {
            margin: 14px 0 18px;
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #f8fafc;
        }

        .pdf-print-grid {
            display: grid;
            grid-template-columns:
                minmax(170px, 1fr)
                minmax(145px, 1fr)
                minmax(145px, 1fr)
                minmax(145px, 1fr)
                auto;
            gap: 10px;
            align-items: end;
        }

        .pdf-print-field label {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .pdf-print-control {
            width: 100%;
            min-height: 41px;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            font-size: 13px;
        }

        .pdf-print-button {
            min-height: 41px;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            background: #16a34a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .pdf-print-button:hover {
            background: #15803d;
        }

        .pdf-print-note {
            margin-top: 10px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .detail-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .detail-table th {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            background: #f3f4f6;
            color: #111827;
            font-weight: 800;
            text-align: left;
            white-space: nowrap;
        }

        .detail-table td {
            padding: 11px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            line-height: 1.45;
        }

        .detail-table tbody tr:hover {
            background: #f9fafb;
        }

        .file-name-cell {
            max-width: 340px;
            line-height: 1.45;
            word-break: break-word;
        }

        .nowrap {
            white-space: nowrap;
        }

        .nopol-cell {
            color: #111827;
            font-weight: 800;
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: fit-content;
        }

        .btn-view,
        .btn-download {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 7px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
        }

        .btn-view {
            background: #2563eb;
        }

        .btn-download {
            background: #16a34a;
        }

        .empty-row {
            padding: 25px !important;
            color: #6b7280;
            text-align: center;
        }

        .detail-pagination {
            margin-top: 18px;
        }

        @media (max-width: 1100px) {
            .filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pdf-print-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* =========================================================
        STATUS ERRORLOG
        ========================================================= */

        .errorlog-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 82px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-closed {
            border: 1px solid #86efac;
            background: #dcfce7;
            color: #166534;
        }

        .status-active {
            border: 1px solid #fecaca;
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-empty {
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            color: #4b5563;
        }


        /* =========================================================
        RINGKASAN VISUAL DETAIL
        ========================================================= */

        .visual-summary-panel {
            margin: 16px 0 20px;
            overflow: hidden;
            border: 1px solid #dbe3f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
        }

        .visual-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .visual-summary-heading {
            min-width: 0;
        }

        .visual-summary-heading h3 {
            margin: 0;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
        }

        .visual-summary-heading p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 10px;
            line-height: 1.5;
        }

        .visual-summary-toggle {
            flex-shrink: 0;
            padding: 7px 11px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
        }

        .visual-summary-toggle:hover {
            background: #dbeafe;
        }

        .visual-summary-body {
            padding: 15px;
        }

        .visual-summary-body.is-hidden {
            display: none;
        }

        .visual-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .visual-stat-card {
            min-width: 0;
            padding: 13px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            background: #f8fafc;
        }

        .visual-stat-card-blue {
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .visual-stat-card-orange {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        .visual-stat-card-green {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .visual-stat-label {
            display: block;
            margin-bottom: 7px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
        }

        .visual-stat-value {
            display: block;
            overflow: hidden;
            color: #111827;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.3;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .visual-stat-description {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 10px;
            line-height: 1.4;
        }

        .visual-chart-card {
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            background: #ffffff;
        }

        .visual-chart-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .visual-chart-title strong {
            display: block;
            color: #111827;
            font-size: 13px;
            font-weight: 800;
        }

        .visual-chart-title span {
            display: block;
            margin-top: 3px;
            color: #6b7280;
            font-size: 10px;
        }

        .visual-chart-badge {
            flex-shrink: 0;
            padding: 5px 9px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 9px;
            font-weight: 800;
        }

        .visual-chart-container {
            position: relative;
            width: 100%;
            height: 245px;
        }

        .visual-chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .visual-chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            color: #6b7280;
            font-size: 12px;
        }


        /* =========================================================
        RESPONSIVE
        ========================================================= */

        @media (max-width: 760px) {
            .visual-stat-grid {
                grid-template-columns: 1fr;
            }

            .visual-summary-header,
            .visual-chart-title {
                align-items: flex-start;
                flex-direction: column;
            }

            .visual-summary-toggle {
                width: 100%;
            }

            .visual-chart-container {
                height: 280px;
            }
        }

        @media (max-width: 640px) {
            .detail-wrapper {
                padding: 14px;
            }

            .filter-grid,
            .pdf-print-grid {
                grid-template-columns: 1fr;
            }

            .btn-filter,
            .btn-reset,
            .pdf-print-button {
                width: 100%;
            }
        }
    </style>

    <div class="detail-wrapper">
        <div class="detail-card">

            {{-- FILTER DATA --}}
            <form
                method="GET"
                action="{{ route('monitoring.detail', $jenis) }}"
                class="filter-grid"
            >
                <div class="filter-group">
                    <label for="search">
                        Cari Data
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari NOPOL, TLPG, driver, event, nama file..."
                        class="filter-control"
                    >
                </div>

                <div class="filter-group">
                    <label for="tanggal">
                        Tanggal Kejadian
                    </label>

                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        value="{{ request('tanggal') }}"
                        class="filter-control"
                    >
                </div>

                <div class="filter-group">
                    <label for="bulan">
                        Bulan Kejadian
                    </label>

                    <input
                        type="month"
                        id="bulan"
                        name="bulan"
                        value="{{ request('bulan') }}"
                        class="filter-control"
                    >
                </div>

                <div class="filter-group">
                    <label for="tlpg">
                        TLPG / Terminal
                    </label>

                    <select
                        id="tlpg"
                        name="tlpg"
                        class="filter-control"
                    >
                        <option value="">
                            Semua TLPG
                        </option>

                        @foreach($tlpgOptions as $tlpg)
                            <option
                                value="{{ $tlpg }}"
                                {{ request('tlpg') === $tlpg ? 'selected' : '' }}
                            >
                                {{ $tlpg }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="event_name">
                        @if($jenis === 'kendala')
                            Jenis Kendala
                        @elseif($jenis === 'accident')
                            Jenis Accident
                        @elseif($jenis === 'errorlog')
                            Jenis Errorlog
                        @else
                            Jenis Pelanggaran
                        @endif
                    </label>

                    <select
                        id="event_name"
                        name="event_name"
                        class="filter-control"
                    >
                        <option value="">
                            Semua Jenis
                        </option>

                        @foreach($eventOptions as $eventName)
                            <option
                                value="{{ $eventName }}"
                                {{ request('event_name') === $eventName ? 'selected' : '' }}
                            >
                                {{ $eventName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($jenis === 'errorlog')
    <div class="filter-group">
        <label for="status_errorlog">
            Status Errorlog
        </label>

        <select
            id="status_errorlog"
            name="status_errorlog"
            class="filter-control"
        >
            <option value="">
                Semua Status
            </option>

            <option
                value="closed"
                {{ request('status_errorlog') === 'closed' ? 'selected' : '' }}
            >
                Closed
            </option>

            <option
                value="aktif"
                {{ request('status_errorlog') === 'aktif' ? 'selected' : '' }}
            >
                Masih Aktif
            </option>

            <option
                value="kosong"
                {{ request('status_errorlog') === 'kosong' ? 'selected' : '' }}
            >
                Belum Ada Status
            </option>
        </select>
    </div>
@endif

                <button
                    type="submit"
                    class="btn-filter"
                >
                    Filter
                </button>

                <a
                    href="{{ route('monitoring.detail', $jenis) }}"
                    class="btn-reset"
                >
                    Reset
                </a>
            </form>

            @php
                $totalFiltered = method_exists($events, 'total')
                    ? $events->total()
                    : $events->count();

                $startNumber = method_exists($events, 'firstItem')
                    ? ($events->firstItem() ?? 1)
                    : 1;

                $colspan = $jenis === 'errorlog'
                    ? 13
                    : 10;
            @endphp

            {{-- TOTAL HASIL FILTER --}}
            <div class="filter-total-box">
                Total data sesuai filter:
                {{ number_format($totalFiltered) }} data
            </div>

            {{-- RINGKASAN VISUAL --}}
            @if(isset($detailSummary))
                <div class="visual-summary-panel">
                    <div class="visual-summary-header">
                        <div class="visual-summary-heading">
                            <h3>Ringkasan Visual</h3>

                            <p>
                                Perhitungan kartu dan grafik mengikuti seluruh
                                filter yang sedang digunakan.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="visualSummaryToggle"
                            class="visual-summary-toggle"
                        >
                            Sembunyikan Ringkasan
                        </button>
                    </div>

                    <div
                        id="visualSummaryBody"
                        class="visual-summary-body"
                    >
                        <div class="visual-stat-grid">
                            <div class="visual-stat-card visual-stat-card-blue">
                                <span class="visual-stat-label">
                                    Total Data
                                </span>

                                <strong class="visual-stat-value">
                                    {{ number_format(
                                        $detailSummary['total'] ?? 0
                                    ) }}
                                </strong>

                                <span class="visual-stat-description">
                                    Jumlah data sesuai filter aktif
                                </span>
                            </div>

                            <div class="visual-stat-card visual-stat-card-orange">
                                <span class="visual-stat-label">
                                    Jenis Terbanyak
                                </span>

                                <strong
                                    class="visual-stat-value"
                                    title="{{ $detailSummary['top_event'] ?? '-' }}"
                                >
                                    {{ $detailSummary['top_event'] ?? '-' }}
                                </strong>

                                <span class="visual-stat-description">
                                    {{ number_format(
                                        $detailSummary['top_event_count'] ?? 0
                                    ) }}
                                    data
                                </span>
                            </div>

                            <div class="visual-stat-card visual-stat-card-green">
                                <span class="visual-stat-label">
                                    TLPG Terbanyak
                                </span>

                                <strong
                                    class="visual-stat-value"
                                    title="{{ $detailSummary['top_tlpg'] ?? '-' }}"
                                >
                                    {{ $detailSummary['top_tlpg'] ?? '-' }}
                                </strong>

                                <span class="visual-stat-description">
                                    {{ number_format(
                                        $detailSummary['top_tlpg_count'] ?? 0
                                    ) }}
                                    data
                                </span>
                            </div>
                        </div>

                        <div class="visual-chart-card">
                            <div class="visual-chart-title">
                                <div>
                                    <strong>
                                        {{ $detailSummary['chart_title']
                                            ?? 'Ringkasan Data' }}
                                    </strong>

                                    <span>
                                        Menampilkan data berdasarkan filter halaman ini.
                                    </span>
                                </div>

                                <span class="visual-chart-badge">
                                    Sesuai Filter
                                </span>
                            </div>

                            @if(
                                !empty(
                                    $detailSummary['chart_labels']
                                    ?? []
                                )
                            )
                                <div class="visual-chart-container">
                                    <canvas id="detailMiniChart"></canvas>
                                </div>
                            @else
                                <div class="visual-chart-empty">
                                    Belum ada data untuk ditampilkan.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- PESAN ERROR --}}
            @if(session('error'))
                <div class="detail-alert-error">
                    {{ session('error') }}
                </div>
            @endif

            {{-- CETAK PDF GABUNGAN --}}
            @if(in_array($jenis, ['pelanggaran', 'kendala', 'accident'], true))
                <div class="pdf-print-card">
                    <form
                        method="GET"
                        action="{{ route('monitoring.cetak-pdf', $jenis) }}"
                        target="_blank"
                        class="pdf-print-grid"
                    >
                        <input
                            type="hidden"
                            name="search"
                            value="{{ request('search') }}"
                        >

                        <input
                            type="hidden"
                            name="tanggal"
                            value="{{ request('tanggal') }}"
                        >

                        <input
                            type="hidden"
                            name="bulan"
                            value="{{ request('bulan') }}"
                        >

                        <input
                            type="hidden"
                            name="tlpg"
                            value="{{ request('tlpg') }}"
                        >

                        <input
                            type="hidden"
                            name="event_name"
                            value="{{ request('event_name') }}"
                        >

                        <div class="pdf-print-field">
                            <label for="rentangCetakPdf">
                                Rentang Cetak PDF
                            </label>

                            <select
                                name="rentang_cetak"
                                id="rentangCetakPdf"
                                class="pdf-print-control"
                            >
                                <option value="sesuai_filter">
                                    Sesuai Filter Detail
                                </option>

                                <option value="harian">
                                    Harian
                                </option>

                                <option value="mingguan">
                                    Mingguan
                                </option>

                                <option value="bulanan">
                                    Bulanan
                                </option>
                            </select>
                        </div>

                        <div
                            id="boxCetakTanggal"
                            class="pdf-print-field"
                        >
                            <label for="cetak_tanggal">
                                Tanggal
                            </label>

                            <input
                                type="date"
                                id="cetak_tanggal"
                                name="cetak_tanggal"
                                value="{{ request('tanggal') }}"
                                class="pdf-print-control"
                            >
                        </div>

                        <div
                            id="boxCetakMinggu"
                            class="pdf-print-field"
                        >
                            <label for="cetak_minggu">
                                Minggu
                            </label>

                            <input
                                type="week"
                                id="cetak_minggu"
                                name="cetak_minggu"
                                class="pdf-print-control"
                            >
                        </div>

                        <div
                            id="boxCetakBulan"
                            class="pdf-print-field"
                        >
                            <label for="cetak_bulan">
                                Bulan
                            </label>

                            <input
                                type="month"
                                id="cetak_bulan"
                                name="cetak_bulan"
                                value="{{ request('bulan') }}"
                                class="pdf-print-control"
                            >
                        </div>

                        <button
                            type="submit"
                            class="pdf-print-button"
                        >
                            Cetak PDF Gabungan
                        </button>
                    </form>

                    <div class="pdf-print-note">
                        File PDF asli akan digabungkan sesuai filter
                        dan rentang cetak yang dipilih.
                    </div>
                </div>
            @endif

            {{-- TABEL DATA --}}
            <div class="table-wrapper">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th style="width:45px;">
                                No
                            </th>

                            <th>Tanggal</th>
                            <th>NOPOL</th>
                            <th>Driver</th>
                            <th>TLPG</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Severity</th>

                            @if($jenis === 'errorlog')
                                <th>No Tiket</th>
                                <th>Status Monitoring</th>
                                <th>Status Sistem</th>
                                <th>Status Tindak Lanjut</th>
                            @endif

                            <th>File</th>

                            @if($jenis !== 'errorlog')
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>
                                    {{ $startNumber + $loop->index }}
                                </td>

                                <td class="nowrap">
                                    {{ $event->event_date
                                        ? \Carbon\Carbon::parse($event->event_date)->format('d-m-Y')
                                        : '-' }}
                                </td>

                                <td class="nopol-cell">
                                    {{ $event->nopol ?: '-' }}
                                </td>

                                <td>
                                    {{ $event->driver_name ?: '-' }}
                                </td>

                                <td>
                                    {{ $event->tlpg ?: '-' }}
                                </td>

                                <td>
                                    {{ $event->event_name ?: '-' }}
                                </td>

                                <td>
                                    {{ $event->category ?: '-' }}
                                </td>

                                <td>
                                    {{ $event->severity ?: '-' }}
                                </td>

                                @if($jenis === 'errorlog')
                                <td>
                                    {{ $event->ticket_number ?: '-' }}
                                </td>

                                <td>
                                    @if($event->monitoring_status === 'closed')
                                        <span class="errorlog-status-badge status-closed">
                                            Closed
                                        </span>
                                    @elseif($event->monitoring_status === 'aktif')
                                        <span class="errorlog-status-badge status-active">
                                            Masih Aktif
                                        </span>
                                    @else
                                        <span class="errorlog-status-badge status-empty">
                                            Belum Ada Status
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $event->event_status ?: '-' }}
                                </td>

                                <td>
                                    {{ $event->follow_up_status ?: '-' }}
                                </td>
                            @endif

                                <td class="file-name-cell">
                                    {{ optional($event->reportUpload)->nama_file ?: '-' }}
                                </td>

                                @if($jenis !== 'errorlog')
                                    <td>
                                        @php
                                            $reportUpload =
                                                $event->reportUpload;

                                            $namaFile =
                                                optional($reportUpload)
                                                    ->nama_file ?? '';

                                            $isPdf =
                                                strtolower(
                                                    pathinfo(
                                                        $namaFile,
                                                        PATHINFO_EXTENSION
                                                    )
                                                ) === 'pdf';
                                        @endphp

                                        @if($reportUpload && $isPdf)
                                            <div class="action-buttons">
                                                <a
                                                    href="{{ route('upload-terpadu.viewer', $reportUpload->id) }}"
                                                    target="_blank"
                                                    class="btn-view"
                                                >
                                                    Lihat
                                                </a>

                                                <a
                                                    href="{{ route('upload-terpadu.download', $reportUpload->id) }}"
                                                    class="btn-download"
                                                >
                                                    Unduh
                                                </a>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ $colspan }}"
                                    class="empty-row"
                                >
                                    Tidak ada data sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($events, 'hasPages') && $events->hasPages())
                <div class="detail-pagination">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleRentangCetakPdf() {
            const select =
                document.getElementById('rentangCetakPdf');

            const boxTanggal =
                document.getElementById('boxCetakTanggal');

            const boxMinggu =
                document.getElementById('boxCetakMinggu');

            const boxBulan =
                document.getElementById('boxCetakBulan');

            if (
                !select ||
                !boxTanggal ||
                !boxMinggu ||
                !boxBulan
            ) {
                return;
            }

            boxTanggal.style.display = 'none';
            boxMinggu.style.display = 'none';
            boxBulan.style.display = 'none';

            if (select.value === 'harian') {
                boxTanggal.style.display = 'block';
            } else if (select.value === 'mingguan') {
                boxMinggu.style.display = 'block';
            } else if (select.value === 'bulanan') {
                boxBulan.style.display = 'block';
            }
        }

        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const select =
                    document.getElementById(
                        'rentangCetakPdf'
                    );

                if (!select) {
                    return;
                }

                toggleRentangCetakPdf();

                select.addEventListener(
                    'change',
                    toggleRentangCetakPdf
                );
            }
        );
    </script>

    @if(
    isset($detailSummary) &&
    !empty(
            $detailSummary['chart_labels']
            ?? []
        )
    )
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {
                    const canvas = document.getElementById(
                        'detailMiniChart'
                    );

                    if (
                        !canvas ||
                        typeof Chart === 'undefined'
                    ) {
                        return;
                    }

                    const chartMode =
                        @json(
                            $detailSummary['chart_mode']
                            ?? 'horizontal'
                        );

                    const labels =
                        @json(
                            $detailSummary['chart_labels']
                            ?? []
                        );

                    const values =
                        @json(
                            $detailSummary['chart_values']
                            ?? []
                        );

                    const pageType =
                        @json($jenis);

                    const baseColors = {
                        pelanggaran: '#3b82f6',
                        kendala: '#f59e0b',
                        accident: '#f97316',
                        errorlog: '#ef4444'
                    };

                    const doughnutColors = [
                        '#22c55e',
                        '#ef4444',
                        '#94a3b8',
                        '#3b82f6',
                        '#f59e0b'
                    ];

                    /*
                    * Label angka pada ujung horizontal bar.
                    */
                    const valueLabelPlugin = {
                        id: 'detailValueLabel',

                        afterDatasetsDraw(chart) {
                            if (
                                chart.config.type !== 'bar'
                            ) {
                                return;
                            }

                            const ctx = chart.ctx;

                            ctx.save();
                            ctx.font =
                                'bold 11px Arial';

                            ctx.fillStyle =
                                '#374151';

                            ctx.textAlign =
                                'left';

                            ctx.textBaseline =
                                'middle';

                            chart.data.datasets.forEach(
                                function (
                                    dataset,
                                    datasetIndex
                                ) {
                                    const meta =
                                        chart.getDatasetMeta(
                                            datasetIndex
                                        );

                                    meta.data.forEach(
                                        function (
                                            element,
                                            index
                                        ) {
                                            const value =
                                                dataset.data[index];

                                            ctx.fillText(
                                                value,
                                                element.x + 7,
                                                element.y
                                            );
                                        }
                                    );
                                }
                            );

                            ctx.restore();
                        }
                    };

                    const isDoughnut =
                        chartMode === 'doughnut';

                    new Chart(
                        canvas,
                        {
                            type: isDoughnut
                                ? 'doughnut'
                                : 'bar',

                            data: {
                                labels: labels,

                                datasets: [
                                    {
                                        label:
                                            'Jumlah Data',

                                        data:
                                            values,

                                        backgroundColor:
                                            isDoughnut
                                                ? doughnutColors
                                                : baseColors[
                                                    pageType
                                                ] || '#3b82f6',

                                        borderColor:
                                            isDoughnut
                                                ? '#ffffff'
                                                : baseColors[
                                                    pageType
                                                ] || '#3b82f6',

                                        borderWidth:
                                            isDoughnut
                                                ? 2
                                                : 1,

                                        borderRadius:
                                            isDoughnut
                                                ? 0
                                                : 5,

                                        maxBarThickness:
                                            34
                                    }
                                ]
                            },

                            plugins: isDoughnut
                                ? []
                                : [valueLabelPlugin],

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                indexAxis:
                                    isDoughnut
                                        ? 'x'
                                        : 'y',

                                layout: {
                                    padding: {
                                        right:
                                            isDoughnut
                                                ? 5
                                                : 35
                                    }
                                },

                                plugins: {
                                    legend: {
                                        display:
                                            isDoughnut,

                                        position:
                                            'right'
                                    },

                                    tooltip: {
                                        callbacks: {
                                            label(context) {
                                                return (
                                                    context.label +
                                                    ': ' +
                                                    context.raw +
                                                    ' data'
                                                );
                                            }
                                        }
                                    }
                                },

                                scales: isDoughnut
                                    ? {}
                                    : {
                                        x: {
                                            beginAtZero: true,

                                            ticks: {
                                                precision: 0
                                            },

                                            grid: {
                                                color:
                                                    'rgba(148, 163, 184, 0.20)'
                                            }
                                        },

                                        y: {
                                            grid: {
                                                display: false
                                            },

                                            ticks: {
                                                autoSkip: false
                                            }
                                        }
                                    }
                            }
                        }
                    );
                }
            );
        </script>
    @endif

    <script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const toggleButton =
                document.getElementById(
                    'visualSummaryToggle'
                );

            const summaryBody =
                document.getElementById(
                    'visualSummaryBody'
                );

            if (!toggleButton || !summaryBody) {
                return;
            }

            toggleButton.addEventListener(
                'click',
                function () {
                    const isHidden =
                        summaryBody.classList.toggle(
                            'is-hidden'
                        );

                    toggleButton.textContent = isHidden
                        ? 'Tampilkan Ringkasan'
                        : 'Sembunyikan Ringkasan';
                }
            );
        }
    );
</script>
</x-app-layout>