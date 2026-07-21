<x-app-layout>
    <x-slot name="header">
        <div class="driver-page-header">
            <div>
                <h2>Detail Skor Pengemudi</h2>

                <p>
                    Rekap skor berdasarkan nama AMT untuk
                    {{ $periodLabel }}.
                </p>
            </div>

            <a
                href="{{ route('dashboard') }}"
                class="back-button"
            >
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $formatDuration = function ($seconds) {
            $seconds = max(
                0,
                (int) $seconds
            );

            $hours = intdiv(
                $seconds,
                3600
            );

            $minutes = intdiv(
                $seconds % 3600,
                60
            );

            return sprintf(
                '%d jam %02d menit',
                $hours,
                $minutes
            );
        };
    @endphp

    <style>
        .driver-wrapper {
            max-width: 1450px;
            margin: 0 auto;
            padding: 24px;
        }

        .driver-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .driver-page-header h2 {
            margin: 0;
            color: #111827;
            font-size: 22px;
            font-weight: 800;
        }

        .driver-page-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .back-button {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 9px;
            background: #4b5563;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .filter-card,
        .table-card,
        .summary-card-driver {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .filter-card {
            margin-bottom: 18px;
            padding: 18px;
            border-radius: 14px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 180px minmax(260px, 1fr) 230px auto;
            gap: 12px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .filter-control {
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
        }

        .filter-button {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .reset-button {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 8px;
            background: #6b7280;
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        .summary-grid-driver {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 18px;
        }

        .summary-card-driver {
            padding: 17px;
            border-radius: 12px;
        }

        .summary-card-driver span {
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .summary-card-driver strong {
            display: block;
            margin-top: 7px;
            color: #111827;
            font-size: 25px;
            font-weight: 900;
        }

        .table-card {
            overflow: hidden;
            border-radius: 14px;
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 17px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-header h3 {
            margin: 0;
            color: #111827;
            font-size: 17px;
            font-weight: 800;
        }

        .table-header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .driver-table {
            width: 100%;
            min-width: 1250px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .driver-table th {
            padding: 11px 10px;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .driver-table td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: top;
        }

        .driver-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .cell-center {
            text-align: center;
        }

        .driver-name {
            color: #111827;
            font-weight: 800;
        }

        .nopol-list {
            max-width: 250px;
            color: #374151;
            line-height: 1.5;
        }

        .event-detail {
            min-width: 170px;
            line-height: 1.6;
        }

        .activity-detail {
            min-width: 170px;
            line-height: 1.6;
        }

        .score-value {
            font-size: 16px;
            font-weight: 900;
        }

        .badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-safe {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-critical {
            background: #7f1d1d;
            color: #ffffff;
        }

        .badge-valid {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-form {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-unidentified {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-row {
            padding: 24px !important;
            color: #6b7280 !important;
            text-align: center;
        }

        .pagination-wrapper {
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 900px) {
            .driver-page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }

            .filter-actions {
                grid-column: span 2;
            }

            .summary-grid-driver {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                grid-column: span 1;
            }

            .summary-grid-driver {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="driver-wrapper">
        <form
            method="GET"
            action="{{ route('monitoring.detail', 'skor-pengemudi') }}"
            class="filter-card"
        >
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="bulan">
                        Bulan
                    </label>

                    <input
                        type="month"
                        id="bulan"
                        name="bulan"
                        value="{{ $selectedMonth }}"
                        class="filter-control"
                    >
                </div>

                <div class="filter-group">
                    <label for="search">
                        Cari Nama AMT atau NOPOL
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Contoh: Agus Santoso atau L 9026 UV"
                        class="filter-control"
                    >
                </div>

                <div class="filter-group">
                    <label for="validation_status">
                        Status Validasi
                    </label>

                    <select
                        id="validation_status"
                        name="validation_status"
                        class="filter-control"
                    >
                        <option value="">
                            Semua Data
                        </option>

                        <option
                            value="registered"
                            @selected($validationStatus === 'registered')
                        >
                            Terdaftar K3-06.1
                        </option>

                        <option
                            value="unregistered"
                            @selected($validationStatus === 'unregistered')
                        >
                            Hanya dari Form
                        </option>

                        <option
                            value="unidentified"
                            @selected($validationStatus === 'unidentified')
                        >
                            Belum Teridentifikasi
                        </option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button
                        type="submit"
                        class="filter-button"
                    >
                        Terapkan
                    </button>

                    <a
                        href="{{ route('monitoring.detail', 'skor-pengemudi') }}"
                        class="reset-button"
                    >
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <div class="summary-grid-driver">
            <div class="summary-card-driver">
                <span>Total Pengemudi</span>

                <strong>
                    {{ number_format($summaryStats['total_driver']) }}
                </strong>
            </div>

            <div class="summary-card-driver">
                <span>Total Kejadian</span>

                <strong>
                    {{ number_format($summaryStats['total_event']) }}
                </strong>
            </div>

            <div class="summary-card-driver">
                <span>Total Risiko</span>

                <strong>
                    {{ number_format($summaryStats['total_risiko']) }}
                </strong>
            </div>

            <div class="summary-card-driver">
                <span>Event Belum Teridentifikasi</span>

                <strong>
                    {{ number_format($summaryStats['unidentified']) }}
                </strong>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <div>
                    <h3>
                        Peringkat Risiko Pengemudi
                    </h3>

                    <p>
                        Peringkat tertinggi menunjukkan total risiko terbesar.
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="driver-table">
                    <thead>
                        <tr>
                            <th>Peringkat</th>
                            <th>Nama AMT</th>
                            <th>NOPOL Digunakan</th>
                            <th>Rincian Kejadian</th>
                            <th>Aktivitas K3-06.1</th>
                            <th>Total Risiko</th>
                            <th>Skor</th>
                            <th>Status Risiko</th>
                            <th>Validasi Nama</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($data as $index => $item)
                            @php
                                $skor = max(
                                    0,
                                    100 - (int) $item->total_risiko
                                );

                                if ($skor >= 85) {
                                    $status = 'Aman';
                                    $statusClass = 'badge-safe';
                                } elseif ($skor >= 70) {
                                    $status = 'Perlu Perhatian';
                                    $statusClass = 'badge-warning';
                                } elseif ($skor >= 50) {
                                    $status = 'Risiko Tinggi';
                                    $statusClass = 'badge-danger';
                                } else {
                                    $status = 'Kritis';
                                    $statusClass = 'badge-critical';
                                }

                                if (
                                    $item->driver_name
                                    === 'AMT BELUM TERIDENTIFIKASI'
                                ) {
                                    $validationLabel =
                                        'Belum Teridentifikasi';

                                    $validationClass =
                                        'badge-unidentified';
                                } elseif (
                                    $item->registered_in_k3061
                                ) {
                                    $validationLabel =
                                        'Terdaftar K3-06.1';

                                    $validationClass =
                                        'badge-valid';
                                } else {
                                    $validationLabel =
                                        'Nama dari Form';

                                    $validationClass =
                                        'badge-form';
                                }
                            @endphp

                            <tr>
                                <td class="cell-center">
                                    {{ $data->firstItem() + $index }}
                                </td>

                                <td>
                                    <span class="driver-name">
                                        {{ $item->driver_name }}
                                    </span>
                                </td>

                                <td>
                                    <div class="nopol-list">
                                        {{ $item->nopol ?: '-' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="event-detail">
                                        Total:
                                        <strong>
                                            {{ $item->total_event }}
                                        </strong>
                                        <br>

                                        Pelanggaran:
                                        {{ $item->total_pelanggaran }}
                                        <br>

                                        Kendala:
                                        {{ $item->total_kendala }}
                                        <br>

                                        Accident:
                                        {{ $item->total_accident }}
                                    </div>
                                </td>

                                <td>
                                    <div class="activity-detail">
                                        Hari aktif:
                                        <strong>
                                            {{ $item->active_days }}
                                        </strong>
                                        <br>

                                        Jarak:
                                        {{ number_format(
                                            $item->total_distance,
                                            2,
                                            ',',
                                            '.'
                                        ) }} km
                                        <br>

                                        Perjalanan:
                                        {{ $formatDuration(
                                            $item->travel_seconds
                                        ) }}
                                    </div>
                                </td>

                                <td class="cell-center">
                                    <strong>
                                        {{ number_format(
                                            $item->total_risiko
                                        ) }}
                                    </strong>
                                </td>

                                <td class="cell-center">
                                    <span class="score-value">
                                        {{ $skor }}
                                    </span>
                                </td>

                                <td class="cell-center">
                                    <span class="badge {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>

                                <td class="cell-center">
                                    <span class="badge {{ $validationClass }}">
                                        {{ $validationLabel }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="9"
                                    class="empty-row"
                                >
                                    Belum ada data skor pengemudi pada
                                    {{ $periodLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrapper">
                {{ $data->links() }}
            </div>
        </div>
    </div>
</x-app-layout>