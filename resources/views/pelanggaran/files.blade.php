<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            File Laporan Terupload
        </h2>
    </x-slot>

    <style>
        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 9px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 14px;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            padding: 12px;
            border: 1px solid #e5e7eb;
        }

        td {
            padding: 12px;
            border: 1px solid #e5e7eb;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>

    <div class="page-wrapper">
        <div class="card">

            @if(session('success'))
                <div class="success">
                    {{ session('success') }}
                </div>
            @endif

            <a href="{{ route('pelanggaran.upload') }}" class="btn-primary">
                Upload File Baru
            </a>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama File</th>
                        <th>Tanggal Laporan</th>
                        <th>Periode</th>
                        <th>Jumlah Pelanggaran</th>
                        <th>Waktu Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($files as $index => $file)
                        <tr>
                            <td>{{ $files->firstItem() + $index }}</td>
                            <td>{{ $file->nama_file }}</td>
                            <td>{{ $file->tanggal_laporan }}</td>
                            <td>{{ $file->periode }}</td>
                            <td>{{ $file->pelanggarans_count }}</td>
                            <td>{{ $file->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                <form action="{{ route('pelanggaran.files.destroy', $file->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus file ini? Semua data pelanggaran dari file ini juga akan terhapus.');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;">
                                Belum ada file laporan yang diupload.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 18px;">
                {{ $files->links() }}
            </div>

        </div>
    </div>
</x-app-layout>