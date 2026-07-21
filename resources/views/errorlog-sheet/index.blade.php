<x-app-layout>
    <x-slot name="header">
        <div class="errorlog-header">
            <div>
                <h2>Sinkronisasi Errorlog Spreadsheet</h2>

                <p>
                    Mengambil data Errorlog langsung dari Google Spreadsheet
                    tanpa mengunduhnya menjadi Excel.
                </p>
            </div>

            <a
                href="{{ route('upload-terpadu.index') }}"
                class="errorlog-back-button"
            >
                Kembali ke Upload Terpadu
            </a>
        </div>
    </x-slot>

    <style>
        .errorlog-wrapper {
            max-width: 1380px;
            margin: 0 auto;
            padding: 24px;
        }

        .errorlog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .errorlog-header h2 {
            margin: 0;
            color: #111827;
            font-size: 21px;
            font-weight: 800;
        }

        .errorlog-header p {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .errorlog-back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            padding: 10px 15px;
            border-radius: 9px;
            background: #6b7280;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }

        .errorlog-back-button:hover {
            background: #4b5563;
        }

        .errorlog-alert {
            margin-bottom: 16px;
            padding: 13px 15px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.5;
        }

        .errorlog-alert-success {
            border: 1px solid #86efac;
            background: #dcfce7;
            color: #166534;
        }

        .errorlog-alert-error {
            border: 1px solid #fecaca;
            background: #fee2e2;
            color: #991b1b;
        }

        .errorlog-info-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
            padding: 15px 17px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .errorlog-info-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 36px;
            height: 36px;
            border-radius: 9px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 17px;
            font-weight: 900;
        }

        .errorlog-info-card strong {
            display: block;
            margin-bottom: 4px;
            color: #1e3a8a;
            font-size: 12px;
        }

        .errorlog-info-card p {
            margin: 0;
            color: #1e40af;
            font-size: 11px;
            line-height: 1.6;
        }

        .errorlog-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 420px) minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .errorlog-card {
            overflow: hidden;
            border: 1px solid #dbe3f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 7px 20px rgba(15, 23, 42, 0.06);
        }

        .errorlog-card-header {
            padding: 17px 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .errorlog-card-header h3 {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 800;
        }

        .errorlog-card-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
        }

        .errorlog-form-body {
            padding: 18px;
        }

        .errorlog-field {
            margin-bottom: 14px;
        }

        .errorlog-field:last-of-type {
            margin-bottom: 0;
        }

        .errorlog-field label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 11px;
            font-weight: 800;
        }

        .errorlog-required {
            color: #dc2626;
        }

        .errorlog-control {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            color: #111827;
            font-size: 12px;
        }

        .errorlog-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .errorlog-field-note {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 10px;
            line-height: 1.5;
        }

        .errorlog-validation-error {
            display: block;
            margin-top: 5px;
            color: #dc2626;
            font-size: 10px;
            font-weight: 700;
        }

        .errorlog-submit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 43px;
            margin-top: 18px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .errorlog-submit-button:hover {
            background: #1d4ed8;
        }

        .errorlog-submit-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }

        .errorlog-process-list {
            display: grid;
            gap: 8px;
            margin-top: 17px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .errorlog-process-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 10px;
        }

        .errorlog-process-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 22px;
            height: 22px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 10px;
            font-weight: 900;
        }

        .errorlog-list-body {
            padding: 17px;
        }

        .errorlog-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .errorlog-summary-card {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f8fafc;
        }

        .errorlog-summary-card span {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
        }

        .errorlog-summary-card strong {
            color: #111827;
            font-size: 19px;
        }

        .errorlog-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .errorlog-table {
            width: 100%;
            min-width: 880px;
            border-collapse: collapse;
            font-size: 11px;
        }

        .errorlog-table th {
            padding: 11px 10px;
            border-bottom: 1px solid #d1d5db;
            background: #f3f4f6;
            color: #374151;
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .errorlog-table td {
            padding: 11px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            vertical-align: middle;
            line-height: 1.45;
        }

        .errorlog-table tbody tr:last-child td {
            border-bottom: none;
        }

        .errorlog-table tbody tr:hover {
            background: #f9fafb;
        }

        .errorlog-period {
            color: #111827;
            font-weight: 800;
            white-space: nowrap;
        }

        .errorlog-sheet-name {
            color: #111827;
            font-weight: 700;
        }

        .errorlog-sheet-link {
            display: block;
            max-width: 260px;
            margin-top: 3px;
            overflow: hidden;
            color: #2563eb;
            font-size: 9px;
            text-decoration: none;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .errorlog-sheet-link:hover {
            text-decoration: underline;
        }

        .errorlog-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
        }

        .errorlog-status-berhasil {
            background: #dcfce7;
            color: #166534;
        }

        .errorlog-status-gagal {
            background: #fee2e2;
            color: #991b1b;
        }

        .errorlog-status-proses {
            background: #fef3c7;
            color: #92400e;
        }

        .errorlog-status-belum_sinkron {
            background: #e5e7eb;
            color: #374151;
        }

        .errorlog-last-error {
            display: block;
            max-width: 230px;
            margin-top: 5px;
            color: #dc2626;
            font-size: 9px;
            line-height: 1.4;
        }

        .errorlog-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .errorlog-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 6px 9px;
            border: none;
            border-radius: 7px;
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .errorlog-action-sync {
            background: #2563eb;
        }

        .errorlog-action-sync:hover {
            background: #1d4ed8;
        }

        .errorlog-action-open {
            background: #0f766e;
        }

        .errorlog-action-open:hover {
            background: #115e59;
        }

        .errorlog-action-delete {
            background: #dc2626;
        }

        .errorlog-action-delete:hover {
            background: #b91c1c;
        }

        .errorlog-empty {
            padding: 38px 20px !important;
            color: #6b7280 !important;
            text-align: center;
        }

        .errorlog-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            margin: 0 auto 10px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 19px;
            font-weight: 900;
        }

        .errorlog-empty strong {
            display: block;
            margin-bottom: 4px;
            color: #374151;
            font-size: 12px;
        }

        .errorlog-empty span {
            font-size: 10px;
        }

        .errorlog-pagination {
            margin-top: 15px;
        }

        @media (max-width: 1050px) {
            .errorlog-main-grid {
                grid-template-columns: 1fr;
            }

            .errorlog-summary {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 700px) {
            .errorlog-wrapper {
                padding: 14px;
            }

            .errorlog-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .errorlog-back-button {
                width: 100%;
                box-sizing: border-box;
            }

            .errorlog-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $namaBulan = [
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

        $totalSumber = $sources->total();

        $totalData = method_exists($sources, 'getCollection')
            ? $sources->getCollection()->sum('total_rows')
            : 0;

        $sumberBerhasil = method_exists($sources, 'getCollection')
            ? $sources->getCollection()->where('status', 'berhasil')->count()
            : 0;
    @endphp

    <div class="errorlog-wrapper">
        @if(session('success'))
            <div class="errorlog-alert errorlog-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="errorlog-alert errorlog-alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="errorlog-info-card">
            <div class="errorlog-info-icon">i</div>

            <div>
                <strong>Sebelum melakukan sinkronisasi</strong>

                <p>
                    Bagikan Google Spreadsheet sebagai
                    <b>Pelihat / Viewer</b>
                    kepada alamat
                    <code>client_email</code>
                    yang terdapat di file
                    <code>service-account.json</code>.
                    SIMOLA hanya akan membaca sheet dan tidak mengubah datanya.
                </p>
            </div>
        </div>

        <div class="errorlog-main-grid">
            <div class="errorlog-card">
                <div class="errorlog-card-header">
                    <h3>Tambah Spreadsheet Errorlog</h3>

                    <p>
                        Setiap bulan boleh menggunakan link Google Spreadsheet
                        yang berbeda.
                    </p>
                </div>

                <div class="errorlog-form-body">
                    <form
                        method="POST"
                        action="{{ route('errorlog-sheet.store') }}"
                        id="errorlog-sync-form"
                    >
                        @csrf

                        <div class="errorlog-field">
                            <label for="spreadsheet_url">
                                Link Google Spreadsheet
                                <span class="errorlog-required">*</span>
                            </label>

                            <input
                                type="url"
                                id="spreadsheet_url"
                                name="spreadsheet_url"
                                value="{{ old('spreadsheet_url') }}"
                                placeholder="https://docs.google.com/spreadsheets/d/..."
                                required
                                class="errorlog-control"
                            >

                            <small class="errorlog-field-note">
                                Tempel link lengkap Google Spreadsheet Errorlog.
                            </small>

                            @error('spreadsheet_url')
                                <span class="errorlog-validation-error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="errorlog-field">
                            <label for="period">
                                Bulan Laporan
                                <span class="errorlog-required">*</span>
                            </label>

                            <input
                                type="month"
                                id="period"
                                name="period"
                                value="{{ old('period') }}"
                                required
                                class="errorlog-control"
                            >

                            <small class="errorlog-field-note">
                                Hanya data yang tanggalnya sesuai bulan ini yang
                                akan disimpan.
                            </small>

                            @error('period')
                                <span class="errorlog-validation-error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="errorlog-field">
                            <label for="sheet_name">
                                Nama Sheet
                                <span class="errorlog-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="sheet_name"
                                name="sheet_name"
                                value="{{ old('sheet_name', 'Error Log System') }}"
                                required
                                class="errorlog-control"
                            >

                            <small class="errorlog-field-note">
                                Gunakan penulisan yang sama persis dengan nama tab
                                pada Google Spreadsheet.
                            </small>

                            @error('sheet_name')
                                <span class="errorlog-validation-error">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            id="errorlog-sync-button"
                            class="errorlog-submit-button"
                        >
                            Sinkronkan Spreadsheet
                        </button>
                    </form>

                    <div class="errorlog-process-list">
                        <div class="errorlog-process-item">
                            <span class="errorlog-process-number">1</span>
                            SIMOLA membaca sheet Error Log System.
                        </div>

                        <div class="errorlog-process-item">
                            <span class="errorlog-process-number">2</span>
                            Data baru ditambahkan dan data lama diperbarui.
                        </div>

                        <div class="errorlog-process-item">
                            <span class="errorlog-process-number">3</span>
                            Data yang sama tidak dibuat menjadi duplikat.
                        </div>
                    </div>
                </div>
            </div>

            <div class="errorlog-card">
                <div class="errorlog-card-header">
                    <h3>Daftar Spreadsheet Errorlog</h3>

                    <p>
                        Kelola dan sinkronkan ulang sumber Errorlog setiap bulan.
                    </p>
                </div>

                <div class="errorlog-list-body">
                    <div class="errorlog-summary">
                        <div class="errorlog-summary-card">
                            <span>Total sumber</span>
                            <strong>{{ number_format($totalSumber) }}</strong>
                        </div>

                        <div class="errorlog-summary-card">
                            <span>Data pada halaman ini</span>
                            <strong>{{ number_format($totalData) }}</strong>
                        </div>

                        <div class="errorlog-summary-card">
                            <span>Status berhasil</span>
                            <strong>{{ number_format($sumberBerhasil) }}</strong>
                        </div>
                    </div>

                    <div class="errorlog-table-wrapper">
                        <table class="errorlog-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Periode</th>
                                    <th>Spreadsheet</th>
                                    <th>Total Data</th>
                                    <th>Terakhir Sinkron</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($sources as $source)
                                    <tr>
                                        <td>
                                            {{ ($sources->firstItem() ?? 1) + $loop->index }}
                                        </td>

                                        <td class="errorlog-period">
                                            {{ $namaBulan[$source->month] ?? $source->month }}
                                            {{ $source->year }}
                                        </td>

                                        <td>
                                            <span class="errorlog-sheet-name">
                                                {{ $source->sheet_name }}
                                            </span>

                                            <a
                                                href="{{ $source->spreadsheet_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="errorlog-sheet-link"
                                                title="{{ $source->spreadsheet_url }}"
                                            >
                                                {{ $source->spreadsheet_url }}
                                            </a>
                                        </td>

                                        <td>
                                            <strong>
                                                {{ number_format($source->total_rows) }}
                                            </strong>
                                        </td>

                                        <td style="white-space: nowrap;">
                                            {{ $source->last_synced_at
                                                ? $source->last_synced_at->format('d-m-Y H:i')
                                                : '-' }}
                                        </td>

                                        <td>
                                            <span class="errorlog-status errorlog-status-{{ $source->status }}">
                                                {{ ucwords(str_replace('_', ' ', $source->status)) }}
                                            </span>

                                            @if($source->last_error)
                                                <span class="errorlog-last-error">
                                                    {{ $source->last_error }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="errorlog-actions">
                                                <form
                                                    method="POST"
                                                    action="{{ route('errorlog-sheet.sync', $source) }}"
                                                    class="errorlog-action-form"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="errorlog-action-button errorlog-action-sync"
                                                    >
                                                        Sinkron Ulang
                                                    </button>
                                                </form>

                                                <a
                                                    href="{{ $source->spreadsheet_url }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="errorlog-action-button errorlog-action-open"
                                                >
                                                    Buka
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="{{ route('errorlog-sheet.destroy', $source) }}"
                                                    onsubmit="return confirm('Hapus sumber spreadsheet beserta seluruh data Errorlog terkait?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="errorlog-action-button errorlog-action-delete"
                                                    >
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="errorlog-empty"
                                        >
                                            <div class="errorlog-empty-icon">+</div>

                                            <strong>
                                                Belum ada spreadsheet Errorlog
                                            </strong>

                                            <span>
                                                Masukkan link Google Spreadsheet
                                                pada formulir di sebelah kiri.
                                            </span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($sources->hasPages())
                        <div class="errorlog-pagination">
                            {{ $sources->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document
            .getElementById('errorlog-sync-form')
            ?.addEventListener('submit', function () {
                const button = document.getElementById(
                    'errorlog-sync-button'
                );

                if (!button) {
                    return;
                }

                button.disabled = true;
                button.textContent = 'Sedang Menyinkronkan...';
            });

        document
            .querySelectorAll('.errorlog-action-form')
            .forEach(function (form) {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('button');

                    if (!button) {
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Proses...';
                });
            });
    </script>
</x-app-layout>