<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h2>Upload Laporan Terpadu</h2>
                <p>Upload laporan Pelanggaran, Kendala, Accident, Errorlog, dan data pendukung skor pengemudi.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .page-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
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
            padding: 24px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
        }

        .help-text {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            cursor: pointer;
        }

        .note-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 18px;
            color: #374151;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 800px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="card">

            @if(session('error'))
                <div class="alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-error">
                    <b>Terjadi kesalahan:</b>
                    <ul style="margin-top:8px; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="note-box">
                <b>Status importer:</b><br>
                Pelanggaran Excel: aktif.<br>
                Errorlog Excel: aktif.<br>
                Kendala PDF: aktif.<br>
                Accident PDF: aktif.
            </div>

                @if(session('upload_summary'))
            @php
            $summary = session('upload_summary');
        @endphp

        <div style="background:#ecfdf5; border:1px solid #86efac; color:#166534; padding:14px; border-radius:10px; margin-bottom:14px;">
            <b>Upload selesai.</b><br>
            Total file dipilih: {{ $summary['total_dipilih'] }}<br>
            File berhasil: {{ $summary['berhasil'] }}<br>
            Data masuk: {{ $summary['data_masuk'] }}<br>
            File duplikat: {{ $summary['duplikat'] }}<br>
            File gagal: {{ $summary['gagal'] }}
        </div>

        @if(!empty($summary['file_duplikat']))
            <div style="background:#fef3c7; border:1px solid #facc15; color:#92400e; padding:14px; border-radius:10px; margin-bottom:14px;">
                <b>File duplikat:</b>
                <ul style="margin-top:8px; padding-left:18px;">
                    @foreach($summary['file_duplikat'] as $file)
                        <li>{{ $file }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($summary['file_gagal']))
            <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:14px; border-radius:10px; margin-bottom:14px;">
                <b>File gagal diproses:</b>
                <ul style="margin-top:8px; padding-left:18px;">
                    @foreach($summary['file_gagal'] as $gagal)
                        <li>
                            <b>{{ $gagal['nama_file'] }}</b><br>
                            <small>{{ $gagal['pesan'] }}</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

        <small style="display:block; margin-top:8px; color:#6b7280;">
            Jika beberapa file gagal, sistem tetap memproses file lain yang valid. File gagal akan ditampilkan pada ringkasan upload.
        </small>

            <form action="{{ route('upload-terpadu.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid-2">
                    <div class="form-group">
                        <label>Jenis Laporan</label>
                        <select name="jenis_laporan" required>
                            <option value="pelanggaran">Pelanggaran</option>
                            <option value="kendala">Kendala</option>
                            <option value="accident">Accident</option>
                            <option value="skor_pengemudi">Skor Pengemudi</option>
                            <option value="errorlog">Errorlog</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Periode</label>
                        <select name="periode" required>
                            <option value="Harian">Harian</option>
                            <option value="Mingguan">Mingguan</option>
                            <option value="Bulanan">Bulanan</option>
                            <option value="Tahunan">Tahunan</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai">
                    </div>

                    <div class="form-group">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Bulan</label>
                        <select name="bulan">
                            <option value="">Pilih Bulan</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tahun</label>
                        <input type="number" name="tahun" placeholder="Contoh: 2026">
                    </div>
                </div>

                <div class="form-group">
                    <label>File Laporan</label>
                    <input type="file" name="file_laporan[]" multiple accept=".xlsx,.xls,.csv,.pdf"
                    style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px;">
                    <div class="help-text">
                        <small>
                            Untuk pelanggaran harian, boleh pilih banyak file PDF sekaligus. Sistem akan membaca tanggal, NOPOL, TLPG, dan jenis pelanggaran dari nama file.
                        </small>
                    </div>
                </div>

                <a href="{{ route('errorlog-sheet.index') }}"
                style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        padding:11px 16px;
                        border-radius:9px;
                        background:#0f766e;
                        color:#ffffff;
                        text-decoration:none;
                        font-size:13px;
                        font-weight:800;">
                    Sinkronkan Errorlog Spreadsheet
                </a>

                <button type="submit" class="btn-primary">
                    Upload & Proses
                </button>
            </form>

        </div>
    </div>
</x-app-layout>