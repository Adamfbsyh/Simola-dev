<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h2>Data Pelanggaran NOPOL</h2>
                <p>Daftar detail pelanggaran berdasarkan NOPOL, terminal, kategori, dan file laporan.</p>
            </div>

            <div style="display:flex; gap:8px;">
    <a href="{{ route('pelanggaran.export', request()->query()) }}" class="btn-secondary">
        Export Excel
    </a>

    <a href="{{ route('pelanggaran.upload') }}" class="btn-primary">
        Upload Excel
    </a>
</div>
        </div>
    </x-slot>

    <style>
        .page-wrapper {
            max-width: 1250px;
            margin: 0 auto;
            padding: 24px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #1f2937;
        }

        .page-header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.4fr 1.2fr auto;
            gap: 12px;
            align-items: end;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 9px 14px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 9px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .summary-text {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 700;
            text-align: left;
            padding: 11px;
            border: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eef2ff;
            color: #3730a3;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        @media (max-width: 1000px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>

    <div class="page-wrapper">

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <form method="GET" action="{{ route('pelanggaran.index') }}">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Cari Data</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NOPOL, terminal, driver, pelanggaran...">
                    </div>

                    <div class="form-group">
                        <label>Tanggal Dari</label>
                        <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}">
                    </div>

                    <div class="form-group">
                        <label>Tanggal Sampai</label>
                        <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}">
                    </div>

                    <div class="form-group">
                        <label>Terminal</label>
                        <select name="terminal">
                            <option value="">Semua Terminal</option>
                            @foreach($terminalOptions as $terminal)
                                <option value="{{ $terminal }}" {{ request('terminal') == $terminal ? 'selected' : '' }}>
                                    {{ $terminal }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori">
                            <option value="">Semua Kategori</option>
                            @foreach($kategoriOptions as $kategori)
                                <option value="{{ $kategori }}" {{ request('kategori') == $kategori ? 'selected' : '' }}>
                                    {{ $kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-primary">
                            Filter
                        </button>

                        <a href="{{ route('pelanggaran.index') }}" class="btn-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="summary-text">
                Total data berdasarkan filter saat ini: <b>{{ $totalData }}</b> pelanggaran.
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>NOPOL</th>
                            <th>Terminal</th>
                            <th>Driver</th>
                            <th>Kategori</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Evidence</th>
                            <th>Row Excel</th>
                            <th>File</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pelanggarans as $item)
                            <tr>
                                <td>{{ $item->tanggal_laporan }}</td>
                                <td><b>{{ $item->nopol }}</b></td>
                                <td>{{ $item->terminal }}</td>
                                <td>{{ $item->driver }}</td>
                                <td>
                                    <span class="badge">{{ $item->kategori_sanksi }}</span>
                                </td>
                                <td>{{ $item->jenis_pelanggaran }}</td>
                                <td>{{ $item->evidence }}</td>
                                <td>{{ $item->row_excel }}</td>
                                <td>{{ $item->laporanFile->nama_file ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center; padding: 18px;">
                                    Tidak ada data pelanggaran sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 18px;">
                {{ $pelanggarans->links() }}
            </div>
        </div>
    </div>
</x-app-layout>