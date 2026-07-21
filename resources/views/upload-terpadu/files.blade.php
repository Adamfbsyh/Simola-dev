<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h2>Riwayat Upload Terpadu</h2>
                <p>Kelola file laporan yang sudah diupload ke sistem monitoring.</p>
            </div>

            <a href="{{ route('upload-terpadu.index') }}" class="btn-primary">
                Upload Baru
            </a>
        </div>
    </x-slot>

    <style>
        .page-wrapper {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
        }

        .page-header p {
            margin: 8px 0 0;
            font-size: 14px;
            color: #6b7280;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 800;
            font-size: 14px;
            display: inline-block;
            white-space: nowrap;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .filter-card,
        .table-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .filter-card {
            padding: 18px;
            margin-bottom: 18px;
        }

        .filter-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto auto;
    gap: 12px;
    align-items: end;
}

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13px;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
        }

        .btn-filter {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 16px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
            height: 42px;
        }

        .btn-reset {
            background: #6b7280;
            color: white;
            padding: 11px 16px;
            border-radius: 8px;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .filter-total {
            margin-top: 14px;
            padding: 11px 13px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            color: #1e3a8a;
            font-size: 13px;
            font-weight: 800;
        }

        .filter-help {
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .table-card {
            padding: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 980px;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-weight: 800;
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        tr:hover {
            background: #f9fafb;
        }

        .file-name {
            max-width: 430px;
            word-break: break-word;
            line-height: 1.5;
            font-weight: 600;
            color: #111827;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .badge-kendala {
            background: #eef2ff;
            color: #3730a3;
        }

        .badge-pelanggaran {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-accident {
            background: #ffedd5;
            color: #c2410c;
        }

        .badge-errorlog {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-default {
            background: #f3f4f6;
            color: #374151;
        }

        .status-success {
            color: #166534;
            font-weight: 800;
        }

        .status-process {
            color: #92400e;
            font-weight: 800;
        }

        .status-fail {
            color: #991b1b;
            font-weight: 800;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            padding: 9px 13px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .empty-box {
            text-align: center;
            padding: 30px;
            color: #6b7280;
            font-weight: 700;
        }

        .pagination-box {
            margin-top: 18px;
        }

        .success-alert {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 13px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 800;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .filter-form {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <div class="page-wrapper">
        @if(session('success'))
            <div class="success-alert">
                {{ session('success') }}
            </div>
        @endif

        @php
            $totalFiltered = method_exists($files, 'total') ? $files->total() : $files->count();
            $startNumber = method_exists($files, 'firstItem') ? ($files->firstItem() ?? 1) : 1;
        @endphp

        @php
    $jenisOptions = $jenisOptions ?? collect(['pelanggaran', 'kendala', 'accident', 'errorlog']);
@endphp

<div class="filter-card">
    <form method="GET" class="filter-form">
        <div class="form-group">
            <label>Cari NOPOL / Nama File</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Contoh: B 9871 SFV, AE 8518 UJ, atau nama file..."
                   class="form-control">
        </div>

        <div class="form-group">
            <label>Jenis Laporan</label>
            <select name="jenis_laporan" class="form-control">
                <option value="">Semua Jenis</option>

                @foreach($jenisOptions as $jenis)
                    <option value="{{ $jenis }}" {{ request('jenis_laporan') === $jenis ? 'selected' : '' }}>
                        {{ ucfirst($jenis) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Tanggal File / Kejadian</label>
            <input type="date"
                name="tanggal"
                value="{{ request('tanggal') }}"
                class="form-control">
        </div>

        <button type="submit" class="btn-filter">
            Filter
        </button>

        <a href="{{ route('upload-terpadu.files') }}" class="btn-reset">
            Reset
        </a>
    </form>

    <div class="filter-total">
        Total file sesuai filter: {{ $totalFiltered }} file
    </div>

    <div class="filter-help">
        Gunakan kolom pencarian untuk mencari berdasarkan NOPOL atau potongan nama file.
        Pilih jenis laporan untuk memisahkan Pelanggaran, Kendala, Accident, atau Errorlog.
        Tanggal File / Kejadian digunakan untuk mencari berdasarkan tanggal pada file, tanggal kejadian, atau tanggal upload.
    </div>
</div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 55px;">No</th>
                            <th>Nama File</th>
                            <th>Jenis Laporan</th>
                            <th>Periode</th>
                            <th>Rentang / Bulan</th>
                            <th>Total Data</th>
                            <th>Status</th>
                            <th>Waktu Upload</th>
                            <th style="width: 95px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($files as $file)
                            @php
                                $jenis = strtolower($file->jenis_laporan ?? '');
                                $badgeClass = match($jenis) {
                                    'pelanggaran' => 'badge-pelanggaran',
                                    'kendala' => 'badge-kendala',
                                    'accident' => 'badge-accident',
                                    'errorlog' => 'badge-errorlog',
                                    default => 'badge-default',
                                };

                                $statusText = strtolower($file->status ?? '');
                                $statusClass = 'status-process';

                                if (str_contains($statusText, 'berhasil')) {
                                    $statusClass = 'status-success';
                                } elseif (str_contains($statusText, 'gagal')) {
                                    $statusClass = 'status-fail';
                                }

                                $bulanList = [
                                    1 => 'Januari',
                                    2 => 'Februari',
                                    3 => 'Maret',
                                    4 => 'April',
                                    5 => 'Mei',
                                    6 => 'Juni',
                                    7 => 'Juli',
                                    8 => 'Agustus',
                                    9 => 'September',
                                    10 => 'Oktober',
                                    11 => 'November',
                                    12 => 'Desember',
                                ];

                                $bulanAngka = (int) ($file->bulan ?? 0);
                                $bulanNama = $bulanList[$bulanAngka] ?? null;
                            @endphp

                            <tr>
                                <td>{{ $startNumber + $loop->index }}</td>

                                <td class="file-name">
                                    {{ $file->nama_file ?? '-' }}
                                </td>

                                <td>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ ucfirst($file->jenis_laporan ?? '-') }}
                                    </span>
                                </td>

                                <td>
                                    {{ ucfirst($file->periode ?? '-') }}
                                </td>

                                <td>
                                    @if($file->tanggal_mulai || $file->tanggal_selesai)
                                        {{ $file->tanggal_mulai ?? '-' }} s/d {{ $file->tanggal_selesai ?? '-' }}
                                    @elseif($bulanNama || $file->tahun)
                                        {{ $bulanNama ? 'Bulan ' . $bulanNama : 'Bulan -' }}
                                        /
                                        Tahun {{ $file->tahun ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ $file->total_data ?? $file->events_count ?? 0 }}
                                </td>

                                <td>
                                    <span class="{{ $statusClass }}">
                                        {{ $file->status ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    {{ optional($file->created_at)->format('d-m-Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    <form action="{{ route('upload-terpadu.destroy', $file->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus file ini? Semua data monitoring dari file ini juga akan ikut terhapus.');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn-delete">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="empty-box">
                                    Tidak ada file sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-box">
                {{ $files->links() }}
            </div>
        </div>
    </div>
</x-app-layout>