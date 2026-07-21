<x-app-layout>
    <x-slot name="header">
        <div class="k32-report-header">
            <div>
                <h2>Laporan K3.2</h2>

                <p>
                    Cetak laporan harian atau bulanan berdasarkan
                    data K3-2.2 Daily yang telah disinkronkan.
                </p>
            </div>

            <a
                href="{{ route('k32.index') }}"
                class="k32-report-back"
            >
                Kembali ke Crosscheck
            </a>
        </div>
    </x-slot>

    <style>
        .k32-report-wrapper {
            max-width: 1050px;
            margin: 0 auto;
            padding: 24px;
        }

        .k32-report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .k32-report-header h2 {
            margin: 0;
            color: #111827;
            font-size: 21px;
            font-weight: 800;
        }

        .k32-report-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .k32-report-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 9px;
            background: #6b7280;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .k32-report-card {
            overflow: hidden;
            border: 1px solid #dbe3f0;
            border-radius: 15px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
        }

        .k32-report-card-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .k32-report-card-header h3 {
            margin: 0;
            color: #111827;
            font-size: 16px;
            font-weight: 800;
        }

        .k32-report-card-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
        }

        .k32-report-form {
            padding: 20px;
        }

        .k32-report-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .k32-report-field {
            min-width: 0;
        }

        .k32-report-field-full {
            grid-column: 1 / -1;
        }

        .k32-report-field label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 11px;
            font-weight: 800;
        }

        .k32-report-control {
            width: 100%;
            min-height: 44px;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            color: #111827;
            font-size: 12px;
        }

        .k32-report-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .k32-report-note {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 10px;
            line-height: 1.5;
        }

        .k32-report-error {
            margin-top: 6px;
            color: #dc2626;
            font-size: 10px;
            font-weight: 700;
        }

        .k32-report-info {
            margin: 18px 0;
            padding: 14px 15px;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 11px;
            line-height: 1.6;
        }

        .k32-report-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 17px;
            border-top: 1px solid #e5e7eb;
        }

        .k32-report-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 17px;
            border: none;
            border-radius: 9px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .k32-report-preview {
            background: #2563eb;
        }

        .k32-report-download {
            background: #16a34a;
        }

        .k32-report-process {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 18px;
        }

        .k32-report-process-item {
            padding: 13px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f8fafc;
        }

        .k32-report-process-item strong {
            display: block;
            margin-bottom: 5px;
            color: #111827;
            font-size: 11px;
        }

        .k32-report-process-item span {
            color: #6b7280;
            font-size: 10px;
            line-height: 1.5;
        }

        @media (max-width: 720px) {
            .k32-report-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .k32-report-wrapper {
                padding: 14px;
            }

            .k32-report-grid,
            .k32-report-process {
                grid-template-columns: 1fr;
            }

            .k32-report-button,
            .k32-report-back {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>

    <div class="k32-report-wrapper">
        @if($errors->any())
            <div
                style="
                    margin-bottom:16px;
                    padding:13px 15px;
                    border:1px solid #fecaca;
                    border-radius:10px;
                    background:#fee2e2;
                    color:#991b1b;
                    font-size:12px;
                    font-weight:700;
                "
            >
                {{ $errors->first() }}
            </div>
        @endif

        <div class="k32-report-card">
            <div class="k32-report-card-header">
                <h3>Filter Cetak Laporan</h3>

                <p>
                    Pilih laporan harian atau bulanan,
                    kemudian tentukan periode dan TLPG.
                </p>
            </div>

            <form
                method="GET"
                class="k32-report-form"
                id="k32ReportForm"
            >
                <div class="k32-report-grid">
                    <div class="k32-report-field">
                        <label for="mode">
                            Jenis Laporan
                        </label>

                        <select
                            id="mode"
                            name="mode"
                            class="k32-report-control"
                            required
                        >
                            <option value="daily">
                                Laporan Harian
                            </option>

                            <option value="monthly">
                                Laporan Bulanan
                            </option>
                        </select>
                    </div>

                    <div class="k32-report-field">
                        <label for="tlpg">
                            TLPG / Terminal
                        </label>

                        <select
                            id="tlpg"
                            name="tlpg"
                            class="k32-report-control"
                        >
                            <option value="">
                                Semua TLPG
                            </option>

                            @foreach($tlpgOptions as $tlpg)
                                <option value="{{ $tlpg }}">
                                    {{ $tlpg }}
                                </option>
                            @endforeach
                        </select>

                        <small class="k32-report-note">
                            Semua TLPG akan dipisahkan per halaman.
                        </small>
                    </div>

                    <div
                        id="dailyField"
                        class="k32-report-field"
                    >
                        <label for="date">
                            Tanggal Laporan
                        </label>

                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="{{ $defaultDate }}"
                            class="k32-report-control"
                        >
                    </div>

                    <div
                        id="monthlyField"
                        class="k32-report-field"
                        style="display:none;"
                    >
                        <label for="month">
                            Bulan Laporan
                        </label>

                        <input
                            type="month"
                            id="month"
                            name="month"
                            value="{{ $defaultMonth }}"
                            class="k32-report-control"
                        >
                    </div>
                </div>

                <div class="k32-report-info">
                    Laporan menggunakan data hasil sinkronisasi
                    <strong>K3-2.2 DAILY</strong>.
                    Untuk laporan bulanan, seluruh nilai pada bulan
                    yang dipilih akan dijumlahkan berdasarkan NOPOL,
                    TLPG, dan jenis pelanggaran.
                </div>

                <div class="k32-report-actions">
                    <button
                        type="submit"
                        formaction="{{ route('k32-report.preview') }}"
                        formtarget="_blank"
                        class="k32-report-button k32-report-preview"
                    >
                        Preview Laporan
                    </button>

                    <button
                        type="submit"
                        formaction="{{ route('k32-report.pdf') }}"
                        name="output"
                        value="download"
                        class="k32-report-button k32-report-download"
                    >
                        Unduh PDF
                    </button>
                </div>

                <div class="k32-report-process">
                    <div class="k32-report-process-item">
                        <strong>1. Pilih Periode</strong>

                        <span>
                            Gunakan tanggal untuk laporan harian
                            atau bulan untuk laporan bulanan.
                        </span>
                    </div>

                    <div class="k32-report-process-item">
                        <strong>2. Pilih TLPG</strong>

                        <span>
                            Pilih salah satu TLPG atau cetak
                            seluruh TLPG sekaligus.
                        </span>
                    </div>

                    <div class="k32-report-process-item">
                        <strong>3. Cetak Laporan</strong>

                        <span>
                            Periksa preview kemudian cetak
                            atau unduh dalam bentuk PDF.
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const mode =
                    document.getElementById('mode');

                const dailyField =
                    document.getElementById(
                        'dailyField'
                    );

                const monthlyField =
                    document.getElementById(
                        'monthlyField'
                    );

                const dateInput =
                    document.getElementById('date');

                const monthInput =
                    document.getElementById('month');

                function updatePeriodField() {
                    const monthly =
                        mode.value === 'monthly';

                    dailyField.style.display =
                        monthly
                            ? 'none'
                            : 'block';

                    monthlyField.style.display =
                        monthly
                            ? 'block'
                            : 'none';

                    dateInput.required =
                        !monthly;

                    monthInput.required =
                        monthly;
                }

                mode.addEventListener(
                    'change',
                    updatePeriodField
                );

                updatePeriodField();
            }
        );
    </script>
</x-app-layout>