<x-app-layout>
    <x-slot name="header">
        <div class="k32-page-header">
            <div>
                <h2>Crosscheck Form K3.2</h2>

                <p>
                    Sinkronisasi mengambil data K3.2 dari Google Sheet
                    sekaligus membaca jam kejadian dari PDF yang sudah tersimpan.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('k32.sync') }}"
                onsubmit="return confirm('Sinkronkan ulang data K3.2 dari Google Sheet?');"
            >
                @csrf

                <button
                    type="submit"
                    class="k32-sync-button"
                >
                    Sinkronkan K3.2
                </button>
            </form>
        </div>
    </x-slot>

    <style>
        .k32-wrapper {
            max-width: 1380px;
            margin: 0 auto;
            padding: 24px;
        }

        .k32-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .k32-page-header h2 {
            margin: 0;
            color: #111827;
            font-size: 22px;
            font-weight: 800;
        }

        .k32-page-header p {
            margin: 7px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .k32-sync-button {
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: white;
            padding: 12px 17px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .k32-alert {
            margin-bottom: 16px;
            padding: 13px 15px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .k32-alert-success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        .k32-alert-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }

        .k32-information {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .k32-info-card {
            background: white;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.05);
        }

        .k32-info-card span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .k32-info-card strong {
            color: #111827;
            font-size: 15px;
        }

        /* .k32-summary-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(145px, 1fr)
            );
            gap: 10px;
            margin-bottom: 18px;
        }

        .k32-summary-card {
            background: white;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.05);
        } */

        .k32-main-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .k32-main-card {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 84px;
            padding: 15px;
            background: #ffffff;
            border: 1px solid #dbe3f0;
            border-radius: 13px;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.05);
        }

        .k32-main-card-success {
            border-color: #bbf7d0;
            background: #f7fff9;
        }

        .k32-main-card-warning {
            border-color: #fed7aa;
            background: #fffaf5;
        }

        .k32-main-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            height: 42px;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 900;
        }

        .k32-main-card-success .k32-main-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .k32-main-card-warning .k32-main-icon {
            background: #ffedd5;
            color: #c2410c;
        }

        .k32-main-card span {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
        }

        .k32-main-card strong {
            display: block;
            color: #111827;
            font-size: 23px;
            line-height: 1;
        }

        .k32-summary-details {
            margin-bottom: 18px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.04);
        }

        .k32-summary-details summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 15px;
            cursor: pointer;
            list-style: none;
        }

        .k32-summary-details summary::-webkit-details-marker {
            display: none;
        }

        .k32-summary-details summary strong {
            display: block;
            margin-bottom: 3px;
            color: #111827;
            font-size: 12px;
        }

        .k32-summary-details summary span {
            color: #6b7280;
            font-size: 11px;
        }

        .k32-summary-arrow {
            flex-shrink: 0;
            padding: 6px 9px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb !important;
            font-size: 10px !important;
            font-weight: 800;
        }

        .k32-summary-details[open] .k32-summary-arrow {
            background: #e5e7eb;
            color: #374151 !important;
        }

        .k32-secondary-summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(120px, 1fr));
            gap: 9px;
            padding: 14px;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .k32-secondary-item {
            padding: 10px 11px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
        }

        .k32-secondary-item span {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 10px;
            line-height: 1.35;
        }

        .k32-secondary-item strong {
            color: #111827;
            font-size: 16px;
        }

        @media (max-width: 1000px) {
            .k32-main-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .k32-secondary-summary {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .k32-main-summary,
            .k32-secondary-summary {
                grid-template-columns: 1fr;
            }

            .k32-summary-details summary {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        .k32-summary-card span {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
        }

        .k32-summary-card strong {
            display: block;
            margin-top: 8px;
            color: #111827;
            font-size: 22px;
        }

        .k32-filter-card,
        .k32-table-card {
            background: white;
            border: 1px solid #dbe3f0;
            border-radius: 14px;
            box-shadow: 0 7px 20px rgba(15, 23, 42, 0.06);
        }

        .k32-filter-card {
            padding: 17px;
            margin-bottom: 18px;
        }

        .k32-filter-grid {
            display: grid;
            grid-template-columns:
                1.6fr
                1fr
                1fr
                1.4fr
                1.2fr
                auto
                auto;
            gap: 10px;
            align-items: end;
        }

        .k32-field label {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
        }

        .k32-control {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            padding: 10px 11px;
            color: #111827;
            font-size: 12px;
        }

        .k32-filter-button,
        .k32-reset-button {
            min-height: 40px;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 12px;
            font-weight: 800;
        }

        .k32-filter-button {
            border: none;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        .k32-reset-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #6b7280;
            color: white;
            text-decoration: none;
        }

        .k32-table-card {
            padding: 17px;
        }

        .k32-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
        }

        .k32-table {
            width: 100%;
            min-width: 1050px;
            height: auto !important;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12px;
        }

        .k32-table thead tr,
        .k32-table tbody tr {
            height: auto !important;
        }

        .k32-table th {
            padding: 11px 9px;
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            color: #111827;
            text-align: left;
            vertical-align: middle;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .k32-table td {
            height: auto !important;
            padding: 10px 9px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            vertical-align: middle;
            line-height: 1.45;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .k32-table tbody tr:hover {
            background: #f9fafb;
        }

        .k32-table .text-center {
            text-align: center;
        }

        .k32-nopol {
            font-weight: 800;
            white-space: nowrap !important;
        }

        .k32-number {
            text-align: center;
            font-size: 13px;
            font-weight: 800;
        }

        .k32-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
            white-space: normal;
        }

        .status-sesuai {
            background: #dcfce7;
            color: #166534;
        }

        .status-sesuai_ada_duplikat {
            background: #fef3c7;
            color: #92400e;
        }

        .status-duplikat_pdf {
            background: #ffedd5;
            color: #9a3412;
        }

        .status-jam_tidak_terbaca {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-kurang_pdf {
            background: #fef3c7;
            color: #92400e;
        }

        .status-lebih_pdf {
            background: #ffedd5;
            color: #9a3412;
        }

        .status-hanya_k32 {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-hanya_pdf {
            background: #ede9fe;
            color: #5b21b6;
        }

        .k32-detail-button {
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: #ffffff;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        .k32-detail-button:hover {
            background: #1d4ed8;
        }

        .k32-detail-row {
            display: none;
        }

        .k32-detail-row.is-open {
            display: table-row;
        }

        .k32-detail-cell {
            padding: 14px !important;
            background: #f8fafc;
        }

        .k32-detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .k32-detail-box {
            padding: 10px;
            border: 1px solid #dbe3f0;
            border-radius: 9px;
            background: #ffffff;
        }

        .k32-detail-box span {
            display: block;
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
        }

        .k32-detail-box strong {
            color: #111827;
            font-size: 13px;
        }

        .k32-detail-section {
            margin-top: 12px;
        }

        .k32-detail-section-title {
            margin: 0 0 7px;
            color: #111827;
            font-size: 12px;
            font-weight: 900;
        }

        .k32-time-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .k32-time-item {
            padding: 7px 9px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 11px;
        }

        .k32-time-duplicate {
            border-color: #fdba74;
            background: #fff7ed;
            color: #9a3412;
        }

        .k32-file-list {
            display: grid;
            gap: 6px;
        }

        .k32-file-link {
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.5;
            text-decoration: none;
            word-break: break-word;
        }

        .k32-file-link:hover {
            text-decoration: underline;
        }

        .k32-empty {
            padding: 28px !important;
            color: #6b7280 !important;
            text-align: center;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .k32-detail-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .k32-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .k32-table tr:hover {
            background: #f9fafb;
        }

        .k32-nopol {
            font-weight: 800;
            white-space: nowrap;
        }

        .k32-number {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
        }

        .k32-status {
            display: inline-block;
            border-radius: 999px;
            padding: 6px 9px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-sesuai {
            background: #dcfce7;
            color: #166534;
        }

        .status-kurang_pdf {
            background: #fef3c7;
            color: #92400e;
        }

        .status-lebih_pdf {
            background: #ffedd5;
            color: #9a3412;
        }

        .status-hanya_k32 {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-hanya_pdf {
            background: #ede9fe;
            color: #5b21b6;
        }

        .k32-file-link {
            display: block;
            margin-bottom: 5px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            word-break: break-word;
        }

        .k32-file-link:hover {
            text-decoration: underline;
        }

        .k32-empty {
            padding: 28px !important;
            color: #6b7280 !important;
            text-align: center;
            font-weight: 700;
        }

        .k32-pagination {
            margin-top: 18px;
        }

        @media (max-width: 1100px) {
            .k32-summary-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .k32-filter-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {
            .k32-wrapper {
                padding: 14px;
            }

            .k32-page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .k32-information,
            .k32-summary-grid,
            .k32-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .k32-time-item {
            margin-bottom: 5px;
            padding: 6px 8px;
            border: 1px solid #dbeafe;
            border-radius: 7px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 11px;
        }

        .k32-time-item strong {
            display: inline-block;
            margin-right: 4px;
        }

        .k32-time-item span,
        .k32-time-item small {
            display: block;
            margin-top: 3px;
        }

        .k32-time-duplicate {
            border-color: #fdba74;
            background: #fff7ed;
            color: #9a3412;
        }

        .status-sesuai_ada_duplikat {
            background: #fef3c7;
            color: #92400e;
        }

        .status-duplikat_pdf {
            background: #ffedd5;
            color: #9a3412;
        }

        .status-jam_tidak_terbaca {
            background: #e0e7ff;
            color: #3730a3;
        }

        .k32-filter-card {
            margin-bottom: 14px;
            padding: 14px;
            background: #ffffff;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.05);
        }

        .k32-filter-form {
            display: grid;
            grid-template-columns:
                minmax(190px, 1.4fr)
                minmax(135px, 0.8fr)
                minmax(135px, 0.8fr)
                minmax(190px, 1.2fr)
                minmax(170px, 1fr)
                auto;
            gap: 9px;
            align-items: end;
        }

        .k32-filter-field label {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 800;
        }

        .k32-filter-control {
            width: 100%;
            min-height: 38px;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font-size: 11px;
        }

        .k32-filter-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .k32-filter-actions {
            display: flex;
            gap: 7px;
        }

        .k32-filter-button,
        .k32-reset-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 13px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .k32-filter-button {
            border: none;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
        }

        .k32-filter-button:hover {
            background: #1d4ed8;
        }

        .k32-reset-button {
            background: #6b7280;
            color: #ffffff;
            text-decoration: none;
        }

        .k32-reset-button:hover {
            background: #4b5563;
        }

        .k32-filter-result {
            margin-top: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 11px;
        }

        @media (max-width: 1100px) {
            .k32-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .k32-filter-actions {
                align-self: end;
            }
        }

        @media (max-width: 650px) {
            .k32-filter-form {
                grid-template-columns: 1fr;
            }

            .k32-filter-actions {
                width: 100%;
            }

            .k32-filter-button,
            .k32-reset-button {
                flex: 1;
            }
        }
    </style>

    <div class="k32-wrapper">
        @if(session('success'))
            <div class="k32-alert k32-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="k32-alert k32-alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="k32-information">
            <div class="k32-info-card">
                <span>Rentang data PDF</span>

                <strong>
                    @if($pdfStart && $pdfEnd)
                        {{ $pdfStart->format('d-m-Y') }}
                        sampai
                        {{ $pdfEnd->format('d-m-Y') }}
                    @else
                        Belum ada data PDF
                    @endif
                </strong>
            </div>

            <div class="k32-info-card">
                <span>Data K3.2 tersimpan</span>

                <strong>
                    {{ number_format($totalStored) }}
                    kombinasi
                </strong>
            </div>

            <div class="k32-info-card">
                <span>Terakhir sinkron</span>

                <strong>
                    @if($lastSyncedAt)
                        {{ \Carbon\Carbon::parse($lastSyncedAt)->format('d-m-Y H:i') }}
                    @else
                        Belum pernah sinkron
                    @endif
                </strong>
            </div>
        </div>

        @php
    /*
     * Menghitung jumlah kombinasi yang masih perlu diperiksa.
     * Angka ini bukan jumlah kejadian, tetapi jumlah baris/kombinasi.
     */
    $perluDiperiksa =
        ($summary['sesuai_ada_duplikat'] ?? 0) +
        ($summary['duplikat_pdf'] ?? 0) +
        ($summary['jam_tidak_terbaca'] ?? 0) +
        ($summary['kurang_pdf'] ?? 0) +
        ($summary['lebih_pdf'] ?? 0) +
        ($summary['hanya_k32'] ?? 0) +
        ($summary['hanya_pdf'] ?? 0);
@endphp

<div class="k32-main-summary">
    <div class="k32-main-card">
        <div class="k32-main-icon">K3.2</div>

        <div>
            <span>Total Kejadian K3.2</span>
            <strong>{{ number_format($summary['total_k32'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="k32-main-card">
        <div class="k32-main-icon">PDF</div>

        <div>
            <span>Kejadian PDF Unik</span>
            <strong>{{ number_format($summary['total_pdf_unique'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="k32-main-card k32-main-card-success">
        <div class="k32-main-icon">✓</div>

        <div>
            <span>Kombinasi Sesuai</span>
            <strong>{{ number_format($summary['sesuai'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="k32-main-card {{ $perluDiperiksa > 0 ? 'k32-main-card-warning' : 'k32-main-card-success' }}">
        <div class="k32-main-icon">
            {{ $perluDiperiksa > 0 ? '!' : '✓' }}
        </div>

        <div>
            <span>Perlu Diperiksa</span>
            <strong>{{ number_format($perluDiperiksa) }}</strong>
        </div>
    </div>
</div>

<details class="k32-summary-details">
    <summary>
        <div>
            <strong>Rincian Hasil Crosscheck</strong>
            <span>Lihat informasi PDF mentah, duplikat, jam kosong, dan selisih data.</span>
        </div>

        <span class="k32-summary-arrow">Buka Rincian</span>
    </summary>

    <div class="k32-secondary-summary">
        <div class="k32-secondary-item">
            <span>Total PDF mentah</span>
            <strong>{{ number_format($summary['total_pdf_raw'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Kemungkinan duplikat</span>
            <strong>{{ number_format($summary['total_duplicate'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Jam belum terbaca</span>
            <strong>{{ number_format($summary['total_time_unreadable'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Sesuai, ada duplikat</span>
            <strong>{{ number_format($summary['sesuai_ada_duplikat'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Perlu cek duplikat</span>
            <strong>{{ number_format($summary['duplikat_pdf'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Kurang di PDF</span>
            <strong>{{ number_format($summary['kurang_pdf'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Lebih di PDF</span>
            <strong>{{ number_format($summary['lebih_pdf'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Hanya di K3.2</span>
            <strong>{{ number_format($summary['hanya_k32'] ?? 0) }}</strong>
        </div>

        <div class="k32-secondary-item">
            <span>Hanya di PDF</span>
            <strong>{{ number_format($summary['hanya_pdf'] ?? 0) }}</strong>
        </div>
    </div>
</details>

</details>

<div class="k32-filter-card">
    <form
        method="GET"
        action="{{ route('k32.index') }}"
        class="k32-filter-form"
    >
        <div class="k32-filter-field k32-filter-search">
            <label for="search">
                Cari NOPOL / TLPG / Jenis
            </label>

            <input
                type="text"
                id="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Contoh: B 9202 TEK"
                class="k32-filter-control"
            >
        </div>

        <div class="k32-filter-field">
            <label for="tanggal">
                Tanggal
            </label>

            <input
                type="date"
                id="tanggal"
                name="tanggal"
                value="{{ request('tanggal') }}"
                class="k32-filter-control"
            >
        </div>

        <div class="k32-filter-field">
            <label for="bulan">
                Bulan
            </label>

            <input
                type="month"
                id="bulan"
                name="bulan"
                value="{{ request('bulan') }}"
                class="k32-filter-control"
            >
        </div>

        <div class="k32-filter-field k32-filter-event">
            <label for="event_name">
                Jenis Pelanggaran
            </label>

            <select
                id="event_name"
                name="event_name"
                class="k32-filter-control"
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

        <div class="k32-filter-field">
            <label for="status">
                Status Crosscheck
            </label>

            <select
                id="status"
                name="status"
                class="k32-filter-control"
            >
                <option value="">
                    Semua Status
                </option>

                @foreach($statusOptions as $code => $label)
                    <option
                        value="{{ $code }}"
                        {{ request('status') === $code ? 'selected' : '' }}
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="k32-filter-actions">
            <button
                type="submit"
                class="k32-filter-button"
            >
                Filter
            </button>

            <a
                href="{{ route('k32.index') }}"
                class="k32-reset-button"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="k32-filter-result">
        Menampilkan
        <strong>{{ number_format($results->total()) }}</strong>
        kombinasi data sesuai filter.
    </div>
</div>

<div class="k32-table-card">
    <div class="k32-table-wrapper">
        <table class="k32-table">
            <colgroup>
                <col style="width:45px;">
                <col style="width:95px;">
                <col style="width:105px;">
                <col style="width:125px;">
                <col style="width:210px;">
                <col style="width:60px;">
                <col style="width:75px;">
                <col style="width:75px;">
                <col style="width:70px;">
                <col style="width:150px;">
                <col style="width:75px;">
            </colgroup>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>NOPOL</th>
                    <th>TLPG</th>
                    <th>Jenis Pelanggaran</th>
                    <th class="text-center">K3.2</th>
                    <th class="text-center">PDF</th>
                    <th class="text-center">Unik</th>
                    <th class="text-center">Duplikat</th>
                    <th>Status</th>
                    <th class="text-center">Detail</th>
                </tr>
            </thead>

            <tbody>
                @forelse($results as $row)
                    @php
                        $detailId = 'detail-' . md5(
                            $row['date'] .
                            $row['nopol'] .
                            $row['event_name']
                        );

                        $statusSingkat = [
                            'sesuai' => 'Sesuai',
                            'sesuai_ada_duplikat' => 'Sesuai, ada duplikat',
                            'duplikat_pdf' => 'Perlu cek duplikat',
                            'jam_tidak_terbaca' => 'Perlu cek jam',
                            'kurang_pdf' => 'Kurang di PDF',
                            'lebih_pdf' => 'Lebih di PDF',
                            'hanya_k32' => 'Hanya di K3.2',
                            'hanya_pdf' => 'Hanya di PDF',
                        ][$row['status_code']] ?? $row['status_label'];
                    @endphp

                    <tr>
                        <td>
                            {{ ($results->firstItem() ?? 1) + $loop->index }}
                        </td>

                        <td style="white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}
                        </td>

                        <td class="k32-nopol">
                            {{ $row['nopol'] }}
                        </td>

                        <td>
                            {{ $row['tlpg_pdf'] ?: ($row['tlpg_k32'] ?: '-') }}
                        </td>

                        <td>
                            {{ $row['event_name'] }}
                        </td>

                        <td class="k32-number">
                            {{ $row['k32_count'] }}
                        </td>

                        <td class="k32-number">
                            {{ $row['pdf_raw_count'] }}
                        </td>

                        <td class="k32-number">
                            {{ $row['pdf_unique_count'] }}
                        </td>

                        <td class="k32-number">
                            {{ $row['pdf_duplicate_count'] }}
                        </td>

                        <td>
                            <span class="k32-status status-{{ $row['status_code'] }}">
                                {{ $statusSingkat }}
                            </span>
                        </td>

                        <td class="text-center">
                            <button
                                type="button"
                                class="k32-detail-button"
                                onclick="toggleK32Detail('{{ $detailId }}', this)"
                            >
                                Lihat
                            </button>
                        </td>
                    </tr>

                    <tr
                        id="{{ $detailId }}"
                        class="k32-detail-row"
                    >
                        <td
                            colspan="11"
                            class="k32-detail-cell"
                        >
                            <div class="k32-detail-grid">
                                <div class="k32-detail-box">
                                    <span>TLPG K3.2</span>
                                    <strong>
                                        {{ $row['tlpg_k32'] ?: '-' }}
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>TLPG PDF</span>
                                    <strong>
                                        {{ $row['tlpg_pdf'] ?: '-' }}
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>PDF mentah / kejadian unik</span>
                                    <strong>
                                        {{ $row['pdf_raw_count'] }}
                                        /
                                        {{ $row['pdf_unique_count'] }}
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>Selisih kejadian unik</span>
                                    <strong>
                                        @if($row['difference'] > 0)
                                            +{{ $row['difference'] }}
                                        @else
                                            {{ $row['difference'] }}
                                        @endif
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>Kemungkinan duplikat</span>
                                    <strong>
                                        {{ $row['pdf_duplicate_count'] }}
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>Jam belum terbaca</span>
                                    <strong>
                                        {{ $row['time_unreadable_count'] }}
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>Status lengkap</span>
                                    <strong>
                                        {{ $row['status_label'] }}
                                    </strong>
                                </div>

                                <div class="k32-detail-box">
                                    <span>Jumlah file PDF</span>
                                    <strong>
                                        {{ count($row['files']) }}
                                    </strong>
                                </div>
                            </div>

                            <div class="k32-detail-section">
                                <h4 class="k32-detail-section-title">
                                    Detail jam kejadian
                                </h4>

                                <div class="k32-time-list">
                                    @forelse($row['time_details'] as $timeDetail)
                                        <div class="k32-time-item {{ $timeDetail['duplicate'] ? 'k32-time-duplicate' : '' }}">
                                            <strong>
                                                {{ $timeDetail['time'] ?: 'Jam belum terbaca' }}
                                            </strong>

                                            @if($timeDetail['count'] > 1)
                                                <span>
                                                    — {{ $timeDetail['count'] }} PDF
                                                </span>
                                            @endif

                                            @if(!empty($timeDetail['shifts']))
                                                <div>
                                                    {{ implode(', ', $timeDetail['shifts']) }}
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <span>
                                            Tidak ada informasi jam.
                                        </span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="k32-detail-section">
                                <h4 class="k32-detail-section-title">
                                    File PDF
                                </h4>

                                <div class="k32-file-list">
                                    @forelse($row['files'] as $file)
                                        <a
                                            href="{{ route('upload-terpadu.viewer', $file['id']) }}"
                                            target="_blank"
                                            class="k32-file-link"
                                        >
                                            {{ $file['name'] }}
                                        </a>
                                    @empty
                                        <span>
                                            Tidak ada file PDF.
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="11"
                            class="k32-empty"
                        >
                            Belum ada hasil crosscheck.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="k32-pagination">
        {{ $results->links() }}
    </div>
</div>
    <script>
    function toggleK32Detail(detailId, button) {
        const detailRow = document.getElementById(detailId);

        if (!detailRow) {
            return;
        }

        const isOpen = detailRow.classList.contains('is-open');

        detailRow.classList.toggle('is-open');

        button.textContent = isOpen
            ? 'Lihat'
            : 'Tutup';
    }
</script>
</x-app-layout>