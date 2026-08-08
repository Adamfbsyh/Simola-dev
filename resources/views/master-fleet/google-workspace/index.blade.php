<x-app-layout>
    <x-slot name="header">
        <div
            class="flex flex-col justify-between gap-3
                   md:flex-row md:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Google Workspace Master Fleet
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Dua akun Google terpisah: K3-02 untuk spreadsheet,
                    Evidence untuk struktur folder bukti operasional.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('master-fleet.pc-set.index') }}"
                    class="inline-flex items-center justify-center
                           rounded-lg border border-gray-300
                           bg-white px-4 py-2 text-sm font-semibold
                           text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Kembali ke PC Set Utama
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-[1500px]
                   space-y-6 px-4 sm:px-6 lg:px-8"
        >
            @if(session('success'))
                <div
                    class="rounded-xl border border-green-200
                           bg-green-50 px-5 py-4
                           text-sm text-green-800"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div
                    class="rounded-xl border border-orange-200
                           bg-orange-50 px-5 py-4
                           text-sm text-orange-800"
                >
                    {{ session('warning') }}

                    @if(session('k302_batch_errors'))
                        <ul class="mt-2 list-disc space-y-1 ps-5">
                            @foreach(session('k302_batch_errors') as $batchError)
                                <li>{{ $batchError }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if(session('error'))
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4
                           text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4
                           text-sm text-red-800"
                >
                    <div class="font-bold">
                        Data belum valid.
                    </div>

                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section
                class="grid gap-4 md:grid-cols-4"
            >
                <div
                    class="rounded-xl border border-gray-200
                           bg-white p-5 shadow-sm"
                >
                    <p class="text-xs font-bold uppercase text-gray-500">
                        Periode Aktif
                    </p>
                    <p class="mt-2 font-bold text-gray-900">
                        {{ $period?->name ?? 'Belum tersedia' }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-white p-5 shadow-sm"
                >
                    <p class="text-xs font-bold uppercase text-gray-500">
                        Kendaraan
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($statistics['total']) }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-blue-200
                           bg-blue-50 p-5 shadow-sm"
                >
                    <p class="text-xs font-bold uppercase text-blue-600">
                        P1
                    </p>
                    <p class="mt-2 text-3xl font-bold text-blue-900">
                        {{ number_format($statistics['p1']) }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-emerald-200
                           bg-emerald-50 p-5 shadow-sm"
                >
                    <p class="text-xs font-bold uppercase text-emerald-600">
                        P2
                    </p>
                    <p class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ number_format($statistics['p2']) }}
                    </p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div
                    class="rounded-xl border border-emerald-200
                           bg-white p-6 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-emerald-600">
                                Koneksi K3-02
                            </p>
                            <h3 class="mt-1 text-lg font-bold text-gray-900">
                                Spreadsheet & Folder K3-02
                            </h3>
                        </div>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-bold
                            {{
                                $k302Account
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-orange-100 text-orange-700'
                            }}"
                        >
                            {{ $k302Account ? 'TERHUBUNG' : 'BELUM TERHUBUNG' }}
                        </span>
                    </div>

                    @if($k302Account)
                        <p class="mt-4 text-sm text-gray-600">
                            Akun:
                            <strong>{{ $k302Account->google_email }}</strong>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Digunakan hanya untuk sinkronisasi tab
                            {{ $sourceSheetName }} dan akses folder K3-02.
                        </p>
                    @else
                        <p class="mt-4 text-sm text-orange-700">
                            Hubungkan akun pemilik K3-02, misalnya
                            rtv.lpg.jatimbalinus.
                        </p>
                    @endif

                    <div
                        class="mt-4 rounded-lg border border-amber-200
                               bg-amber-50 p-4 text-sm text-amber-900"
                    >
                        <p class="font-semibold">
                            Proses berjalan melalui background worker.
                        </p>
                        <p class="mt-1">
                            Setelah menekan tombol, jalankan
                            <code class="rounded bg-white px-1 py-0.5">
                                run-simola-evidence-worker.bat
                            </code>
                            dari folder proyek. Halaman boleh ditutup;
                            progres dapat dilihat kembali pada Riwayat Proses.
                        </p>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a
                            href="{{
                                route(
                                    'master-fleet.google-workspace.connect',
                                    ['purpose' => 'k302']
                                )
                            }}"
                            class="rounded-lg bg-emerald-600 px-4 py-2
                                   text-sm font-semibold text-white
                                   hover:bg-emerald-700"
                        >
                            {{ $k302Account ? 'Ganti Akun K3-02' : 'Hubungkan Akun K3-02' }}
                        </a>

                        @if($k302Account)
                            <form
                                method="POST"
                                action="{{
                                    route(
                                        'master-fleet.google-workspace.disconnect',
                                        ['purpose' => 'k302']
                                    )
                                }}"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-lg border border-red-300
                                           bg-white px-4 py-2 text-sm
                                           font-semibold text-red-700
                                           hover:bg-red-50"
                                >
                                    Putuskan K3-02
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div
                    class="rounded-xl border border-blue-200
                           bg-white p-6 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-blue-600">
                                Koneksi Evidence
                            </p>
                            <h3 class="mt-1 text-lg font-bold text-gray-900">
                                Folder Evidence
                            </h3>
                        </div>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-bold
                            {{
                                $evidenceAccount
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-orange-100 text-orange-700'
                            }}"
                        >
                            {{ $evidenceAccount ? 'TERHUBUNG' : 'BELUM TERHUBUNG' }}
                        </span>
                    </div>

                    @if($evidenceAccount)
                        <p class="mt-4 text-sm text-gray-600">
                            Akun:
                            <strong>{{ $evidenceAccount->google_email }}</strong>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Digunakan hanya untuk membuat folder bulan,
                            tanggal, PC, nopol, dan empat kategori Evidence.
                        </p>
                    @else
                        <p class="mt-4 text-sm text-orange-700">
                            Hubungkan akun pemilik Evidence, misalnya
                            evidencejatimbalinus.
                        </p>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a
                            href="{{
                                route(
                                    'master-fleet.google-workspace.connect',
                                    ['purpose' => 'evidence']
                                )
                            }}"
                            class="rounded-lg bg-blue-600 px-4 py-2
                                   text-sm font-semibold text-white
                                   hover:bg-blue-700"
                        >
                            {{ $evidenceAccount ? 'Ganti Akun Evidence' : 'Hubungkan Akun Evidence' }}
                        </a>

                        @if($evidenceAccount)
                            <form
                                method="POST"
                                action="{{
                                    route(
                                        'master-fleet.google-workspace.disconnect',
                                        ['purpose' => 'evidence']
                                    )
                                }}"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-lg border border-red-300
                                           bg-white px-4 py-2 text-sm
                                           font-semibold text-red-700
                                           hover:bg-red-50"
                                >
                                    Putuskan Evidence
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <section
                    class="rounded-xl border border-gray-200
                           bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-bold text-gray-900">
                        Spreadsheet Master PC Set
                    </h3>

                    <p class="mt-2 text-sm text-gray-600">
                        SIMOLA menulis snapshot terbaru ke tab
                        <strong>{{ $sourceSheetName }}</strong>.
                        Seluruh spreadsheet harian dapat mengambil
                        NOPOL dan TLPG dari tab ini melalui IMPORTRANGE.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($spreadsheetUrl)
                            <a
                                href="{{ $spreadsheetUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-lg border border-gray-300
                                       bg-white px-4 py-2
                                       text-sm font-semibold text-gray-700
                                       hover:bg-gray-50"
                            >
                                Buka Spreadsheet Sumber
                            </a>
                        @endif

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'master-fleet.google-workspace.sync-spreadsheet'
                                )
                            }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                @disabled(!$k302Account || !$period)
                                class="rounded-lg bg-emerald-600
                                       px-4 py-2 text-sm font-semibold
                                       text-white hover:bg-emerald-700
                                       disabled:cursor-not-allowed
                                       disabled:opacity-50"
                            >
                                Sinkronkan PC Set Sekarang
                            </button>
                        </form>
                    </div>

                    <details
                        data-simola-collapse="formula"
                        class="simola-collapse mt-5 overflow-hidden
                               rounded-lg border border-blue-200
                               bg-blue-50"
                    >
                        <summary
                            class="flex cursor-pointer items-center
                                   justify-between gap-4 px-4 py-3
                                   text-sm font-bold text-blue-900"
                        >
                            <span>
                                Formula K3-02.2
                                <span
                                    class="ms-2 text-xs font-normal
                                           text-blue-700"
                                >
                                    NOPOL, TLPG, dan PC Final
                                </span>
                            </span>

                            <span
                                class="simola-collapse-chevron
                                       text-lg text-blue-700"
                                aria-hidden="true"
                            >
                                ⌄
                            </span>
                        </summary>

                        <div class="border-t border-blue-200 p-4">

                        <p class="mt-3 text-xs font-semibold text-blue-800">
                            {{ $k302NopolStartCell }} — NOPOL
                        </p>

                        <textarea
                            readonly
                            rows="3"
                            class="mt-1 w-full rounded-md border-blue-200
                                   bg-white text-xs text-gray-800"
                        >{{ $k302NopolFormula }}</textarea>

                        <p class="mt-3 text-xs font-semibold text-blue-800">
                            {{ $k302TlpgStartCell }} — TLPG
                        </p>

                        <textarea
                            readonly
                            rows="3"
                            class="mt-1 w-full rounded-md border-blue-200
                                   bg-white text-xs text-gray-800"
                        >{{ $k302TlpgFormula }}</textarea>

                        <p class="mt-3 text-xs font-semibold text-blue-800">
                            {{ $k302PcStartCell }} — PC FINAL
                        </p>

                        <textarea
                            readonly
                            rows="3"
                            class="mt-1 w-full rounded-md border-blue-200
                                   bg-white text-xs text-gray-800"
                        >{{ $k302PcFormula }}</textarea>

                        <p class="mt-3 text-xs text-blue-800">
                            Tempel formula ini satu kali pada template
                            spreadsheet harian. Salinan berikutnya akan
                            tetap membaca data terbaru dari SIMOLA.
                        </p>
                        </div>
                    </details>
                </section>

                <details
                    data-simola-collapse="evidence-generator"
                    @if(old('evidence_generation_mode')) open @endif
                    class="simola-collapse overflow-hidden rounded-xl
                           border border-blue-200 bg-white shadow-sm"
                >
                    <summary
                        class="flex cursor-pointer items-center
                               justify-between gap-4 px-6 py-5"
                    >
                        <div>
                            <p
                                class="text-xs font-bold uppercase
                                       tracking-wide text-blue-600"
                            >
                                Evidence
                            </p>
                            <h3
                                class="mt-1 text-lg font-bold
                                       text-gray-900"
                            >
                                Generator Folder Evidence
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Harian, mingguan, bulanan, atau rentang khusus
                            </p>
                        </div>

                        <span
                            class="simola-collapse-chevron
                                   text-xl text-blue-600"
                            aria-hidden="true"
                        >
                            ⌄
                        </span>
                    </summary>

                    <div
                        class="border-t border-blue-100
                               px-6 pb-6 pt-1"
                    >
                        <p class="mt-4 text-sm text-gray-600">
                            Struktur: BULAN → TANGGAL → PC → NOPOL →
                            PELANGGARAN, ERRORLOG, ACCIDENT, INSIDEN.
                        </p>

                    <form
                        method="POST"
                        action="{{
                            route(
                                'master-fleet.google-workspace.generate-evidence'
                            )
                        }}"
                        class="mt-5 space-y-4"
                        onsubmit="
                            return confirm(
                                'Seluruh tanggal yang dipilih akan dimasukkan ke background queue Evidence. Proses mingguan atau bulanan dapat berlangsung lama. Lanjutkan?'
                            );
                        "
                    >
                        @csrf

                        <div class="grid gap-4 lg:grid-cols-4">
                            <div>
                                <label
                                    for="evidence-generation-mode"
                                    class="mb-2 block text-sm font-semibold
                                           text-gray-700"
                                >
                                    Mode Pembuatan
                                </label>

                                <select
                                    id="evidence-generation-mode"
                                    name="evidence_generation_mode"
                                    class="w-full rounded-lg border-gray-300
                                           shadow-sm focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                                    <option
                                        value="daily"
                                        @selected(old('evidence_generation_mode', 'daily') === 'daily')
                                    >
                                        Harian — 1 tanggal
                                    </option>
                                    <option
                                        value="weekly"
                                        @selected(old('evidence_generation_mode') === 'weekly')
                                    >
                                        Mingguan — Senin s.d. Minggu
                                    </option>
                                    <option
                                        value="monthly"
                                        @selected(old('evidence_generation_mode') === 'monthly')
                                    >
                                        Bulanan — pilih bulan
                                    </option>
                                    <option
                                        value="range"
                                        @selected(old('evidence_generation_mode') === 'range')
                                    >
                                        Rentang Khusus — tanggal mulai s.d. akhir
                                    </option>
                                </select>
                            </div>

                            <div
                                id="evidence-date-field"
                                class="evidence-mode-field"
                            >
                                <label
                                    for="evidence-workspace-date"
                                    class="mb-2 block text-sm font-semibold
                                           text-gray-700"
                                >
                                    Tanggal Acuan
                                </label>

                                <input
                                    id="evidence-workspace-date"
                                    type="date"
                                    name="evidence_workspace_date"
                                    value="{{ old(
                                        'evidence_workspace_date',
                                        now()->toDateString()
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300
                                           shadow-sm focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                            </div>

                            <div
                                id="evidence-month-field"
                                class="evidence-mode-field hidden"
                            >
                                <label
                                    for="evidence-workspace-month"
                                    class="mb-2 block text-sm font-semibold
                                           text-gray-700"
                                >
                                    Bulan
                                </label>

                                <input
                                    id="evidence-workspace-month"
                                    type="month"
                                    name="evidence_workspace_month"
                                    value="{{ old(
                                        'evidence_workspace_month',
                                        now()->format('Y-m')
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300
                                           shadow-sm focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                            </div>

                            <div
                                id="evidence-start-field"
                                class="evidence-mode-field hidden"
                            >
                                <label
                                    for="evidence-start-date"
                                    class="mb-2 block text-sm font-semibold
                                           text-gray-700"
                                >
                                    Tanggal Mulai
                                </label>

                                <input
                                    id="evidence-start-date"
                                    type="date"
                                    name="evidence_start_date"
                                    value="{{ old(
                                        'evidence_start_date',
                                        now()->toDateString()
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300
                                           shadow-sm focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                            </div>

                            <div
                                id="evidence-end-field"
                                class="evidence-mode-field hidden"
                            >
                                <label
                                    for="evidence-end-date"
                                    class="mb-2 block text-sm font-semibold
                                           text-gray-700"
                                >
                                    Tanggal Akhir
                                </label>

                                <input
                                    id="evidence-end-date"
                                    type="date"
                                    name="evidence_end_date"
                                    value="{{ old(
                                        'evidence_end_date',
                                        now()->toDateString()
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300
                                           shadow-sm focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                            </div>
                        </div>

                        <div
                            class="flex flex-col gap-3 rounded-lg
                                   border border-blue-200 bg-blue-50
                                   p-4 lg:flex-row lg:items-center
                                   lg:justify-between"
                        >
                            <div class="text-sm text-blue-900">
                                <p
                                    id="evidence-mode-help"
                                    class="font-medium"
                                >
                                    Harian membuat satu struktur folder untuk tanggal yang dipilih.
                                </p>
                                <p class="mt-1 text-xs text-blue-800">
                                    Pekerjaan diproses otomatis oleh Windows Evidence Worker.
                                    Bulanan dan rentang panjang dapat memerlukan beberapa jam.
                                </p>
                            </div>

                            <button
                                type="submit"
                                @disabled(!$evidenceAccount || !$period)
                                class="rounded-lg bg-blue-600
                                       px-5 py-2.5 text-sm font-semibold
                                       text-white hover:bg-blue-700
                                       disabled:cursor-not-allowed
                                       disabled:opacity-50"
                            >
                                Antrekan Folder Evidence
                            </button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const mode = document.getElementById(
                                'evidence-generation-mode'
                            );

                            if (!mode) {
                                return;
                            }

                            const dateField = document.getElementById(
                                'evidence-date-field'
                            );
                            const monthField = document.getElementById(
                                'evidence-month-field'
                            );
                            const startField = document.getElementById(
                                'evidence-start-field'
                            );
                            const endField = document.getElementById(
                                'evidence-end-field'
                            );
                            const help = document.getElementById(
                                'evidence-mode-help'
                            );

                            const dateInput = document.getElementById(
                                'evidence-workspace-date'
                            );
                            const monthInput = document.getElementById(
                                'evidence-workspace-month'
                            );
                            const startInput = document.getElementById(
                                'evidence-start-date'
                            );
                            const endInput = document.getElementById(
                                'evidence-end-date'
                            );

                            const setVisible = (element, visible) => {
                                element?.classList.toggle(
                                    'hidden',
                                    !visible
                                );
                            };

                            const refresh = () => {
                                const selected = mode.value;

                                setVisible(
                                    dateField,
                                    ['daily', 'weekly'].includes(selected)
                                );
                                setVisible(
                                    monthField,
                                    selected === 'monthly'
                                );
                                setVisible(
                                    startField,
                                    selected === 'range'
                                );
                                setVisible(
                                    endField,
                                    selected === 'range'
                                );

                                dateInput.required = [
                                    'daily',
                                    'weekly',
                                ].includes(selected);
                                monthInput.required =
                                    selected === 'monthly';
                                startInput.required =
                                    selected === 'range';
                                endInput.required =
                                    selected === 'range';

                                const messages = {
                                    daily:
                                        'Harian membuat satu struktur folder untuk tanggal yang dipilih.',
                                    weekly:
                                        'Mingguan mengantrekan tujuh tanggal, Senin sampai Minggu.',
                                    monthly:
                                        'Bulanan mengantrekan tanggal 1 sampai akhir bulan yang dipilih.',
                                    range:
                                        'Rentang Khusus mengantrekan setiap tanggal dari tanggal mulai sampai tanggal akhir.',
                                };

                                help.textContent =
                                    messages[selected]
                                    ?? messages.daily;
                            };

                            mode.addEventListener(
                                'change',
                                refresh
                            );

                            refresh();
                        });
                    </script>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @if($evidenceFolderUrl)
                            <a
                                href="{{ $evidenceFolderUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-lg border border-gray-300
                                       bg-white px-4 py-2
                                       text-sm font-semibold text-gray-700
                                       hover:bg-gray-50"
                            >
                                Buka Folder Evidence
                            </a>
                        @endif

                        @if($k302FolderUrl)
                            <a
                                href="{{ $k302FolderUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-lg border border-gray-300
                                       bg-white px-4 py-2
                                       text-sm font-semibold text-gray-700
                                       hover:bg-gray-50"
                            >
                                Buka Folder K3-02 Private
                            </a>
                        @endif
                    </div>

                    <p class="mt-4 text-xs text-gray-500">
                        Generator Evidence selalu memakai akun Evidence.
                        Spreadsheet PC Set selalu memakai akun K3-02. Kedua
                        folder tetap Restricted dan tidak perlu saling dibagikan.
                    </p>
                    </div>
                </details>
            </div>

            <section
                class="rounded-xl border border-emerald-200
                       bg-white p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">
                            K3-02 Harian Operator
                        </p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            Generator K3-02 Harian, Mingguan, dan Bulanan
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Membuat satu atau banyak file sekaligus. Hasil
                            tetap tersusun BULAN → TANGGAL → satu salinan
                            template K3-02.2 untuk setiap tanggal. Spreadsheet
                            master SIMOLA_PC_SET tidak ikut disalin.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if($k302TemplateUrl)
                            <a
                                href="{{ $k302TemplateUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-lg border border-gray-300
                                       bg-white px-4 py-2 text-sm
                                       font-semibold text-gray-700
                                       hover:bg-gray-50"
                            >
                                Buka Template K3-02
                            </a>
                        @endif

                        @if($k302FolderUrl)
                            <a
                                href="{{ $k302FolderUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-lg border border-gray-300
                                       bg-white px-4 py-2 text-sm
                                       font-semibold text-gray-700
                                       hover:bg-gray-50"
                            >
                                Buka Folder K3-02
                            </a>
                        @endif
                    </div>
                </div>

                <details
                    data-simola-collapse="k302-generator"
                    @if(
                        old('generation_mode')
                        || session('generated_k302_url')
                    )
                        open
                    @endif
                    class="simola-collapse mt-5 overflow-hidden
                           rounded-xl border border-emerald-200"
                >
                    <summary
                        class="flex cursor-pointer items-center
                               justify-between gap-4 bg-emerald-50
                               px-5 py-4"
                    >
                        <div>
                            <p class="font-bold text-emerald-900">
                                Form dan Daftar Spreadsheet K3-02
                            </p>
                            <p class="mt-1 text-xs text-emerald-700">
                                Buka saat ingin membuat atau melihat file
                            </p>
                        </div>

                        <span
                            class="simola-collapse-chevron
                                   text-xl text-emerald-700"
                            aria-hidden="true"
                        >
                            ⌄
                        </span>
                    </summary>

                    <div
                        class="border-t border-emerald-200
                               px-5 pb-5 pt-1"
                    >
                <form
                    method="POST"
                    action="{{
                        route(
                            'master-fleet.google-workspace.generate-k302-daily'
                        )
                    }}"
                    class="mt-5 space-y-4"
                    onsubmit="
                        return confirm(
                            'Buat atau sinkronkan seluruh spreadsheet K3-02 sesuai tanggal yang dipilih? Proses panjang dapat memerlukan beberapa menit.'
                        );
                    "
                >
                    @csrf

                    <div class="grid gap-4 lg:grid-cols-4">
                        <div>
                            <label
                                for="k302-generation-mode"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                            >
                                Mode Pembuatan
                            </label>

                            <select
                                id="k302-generation-mode"
                                name="generation_mode"
                                class="w-full rounded-lg border-gray-300
                                       shadow-sm focus:border-emerald-500
                                       focus:ring-emerald-500"
                            >
                                <option
                                    value="daily"
                                    @selected(old('generation_mode', 'daily') === 'daily')
                                >
                                    Harian — 1 tanggal
                                </option>
                                <option
                                    value="weekly"
                                    @selected(old('generation_mode') === 'weekly')
                                >
                                    Mingguan — Senin s.d. Minggu
                                </option>
                                <option
                                    value="monthly"
                                    @selected(old('generation_mode') === 'monthly')
                                >
                                    Bulanan — pilih bulan
                                </option>
                                <option
                                    value="range"
                                    @selected(old('generation_mode') === 'range')
                                >
                                    Rentang Khusus — tanggal mulai s.d. akhir
                                </option>
                            </select>
                        </div>

                        <div
                            id="k302-date-field"
                            class="k302-mode-field"
                        >
                            <label
                                for="k302-workspace-date"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                            >
                                Tanggal Acuan
                            </label>

                            <input
                                id="k302-workspace-date"
                                type="date"
                                name="workspace_date"
                                value="{{ old(
                                    'workspace_date',
                                    now()->toDateString()
                                ) }}"
                                class="w-full rounded-lg border-gray-300
                                       shadow-sm focus:border-emerald-500
                                       focus:ring-emerald-500"
                            >
                        </div>

                        <div
                            id="k302-month-field"
                            class="k302-mode-field hidden"
                        >
                            <label
                                for="k302-workspace-month"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                            >
                                Bulan
                            </label>

                            <input
                                id="k302-workspace-month"
                                type="month"
                                name="workspace_month"
                                value="{{ old(
                                    'workspace_month',
                                    now()->format('Y-m')
                                ) }}"
                                class="w-full rounded-lg border-gray-300
                                       shadow-sm focus:border-emerald-500
                                       focus:ring-emerald-500"
                            >
                        </div>

                        <div
                            id="k302-start-field"
                            class="k302-mode-field hidden"
                        >
                            <label
                                for="k302-start-date"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                            >
                                Tanggal Mulai
                            </label>

                            <input
                                id="k302-start-date"
                                type="date"
                                name="start_date"
                                value="{{ old(
                                    'start_date',
                                    now()->toDateString()
                                ) }}"
                                class="w-full rounded-lg border-gray-300
                                       shadow-sm focus:border-emerald-500
                                       focus:ring-emerald-500"
                            >
                        </div>

                        <div
                            id="k302-end-field"
                            class="k302-mode-field hidden"
                        >
                            <label
                                for="k302-end-date"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                            >
                                Tanggal Akhir
                            </label>

                            <input
                                id="k302-end-date"
                                type="date"
                                name="end_date"
                                value="{{ old(
                                    'end_date',
                                    now()->toDateString()
                                ) }}"
                                class="w-full rounded-lg border-gray-300
                                       shadow-sm focus:border-emerald-500
                                       focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    <div
                        class="flex flex-col gap-3 rounded-lg
                               border border-emerald-200 bg-emerald-50
                               p-4 lg:flex-row lg:items-center
                               lg:justify-between"
                    >
                        <p
                            id="k302-mode-help"
                            class="text-sm text-emerald-800"
                        >
                            Harian membuat satu file untuk tanggal yang dipilih.
                        </p>

                        <button
                            type="submit"
                            @disabled(!$k302Account || !$period)
                            class="rounded-lg bg-emerald-600 px-5 py-2.5
                                   text-sm font-semibold text-white
                                   hover:bg-emerald-700
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50"
                        >
                            Buat K3-02 Sekaligus
                        </button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const mode = document.getElementById(
                            'k302-generation-mode'
                        );

                        if (!mode) {
                            return;
                        }

                        const dateField = document.getElementById(
                            'k302-date-field'
                        );
                        const monthField = document.getElementById(
                            'k302-month-field'
                        );
                        const startField = document.getElementById(
                            'k302-start-field'
                        );
                        const endField = document.getElementById(
                            'k302-end-field'
                        );
                        const help = document.getElementById(
                            'k302-mode-help'
                        );

                        const dateInput = document.getElementById(
                            'k302-workspace-date'
                        );
                        const monthInput = document.getElementById(
                            'k302-workspace-month'
                        );
                        const startInput = document.getElementById(
                            'k302-start-date'
                        );
                        const endInput = document.getElementById(
                            'k302-end-date'
                        );

                        const setVisible = (element, visible) => {
                            element?.classList.toggle(
                                'hidden',
                                !visible
                            );
                        };

                        const refresh = () => {
                            const selected = mode.value;

                            setVisible(
                                dateField,
                                ['daily', 'weekly'].includes(selected)
                            );
                            setVisible(
                                monthField,
                                selected === 'monthly'
                            );
                            setVisible(
                                startField,
                                selected === 'range'
                            );
                            setVisible(
                                endField,
                                selected === 'range'
                            );

                            dateInput.required = [
                                'daily',
                                'weekly',
                            ].includes(selected);

                            monthInput.required =
                                selected === 'monthly';

                            startInput.required =
                                selected === 'range';

                            endInput.required =
                                selected === 'range';

                            const messages = {
                                daily:
                                    'Harian membuat satu file untuk tanggal yang dipilih.',
                                weekly:
                                    'Mingguan membuat tujuh file, Senin sampai Minggu dari tanggal acuan.',
                                monthly:
                                    'Bulanan membuat seluruh tanggal dari bulan yang dipilih.',
                                range:
                                    'Rentang Khusus membuat setiap tanggal dari tanggal mulai sampai tanggal akhir.',
                            };

                            help.textContent =
                                messages[selected]
                                ?? messages.daily;
                        };

                        mode.addEventListener(
                            'change',
                            refresh
                        );

                        refresh();
                    });
                </script>

                @if(session('generated_k302_url'))
                    <div
                        class="mt-5 rounded-lg border border-green-200
                               bg-green-50 p-4"
                    >
                        <p class="text-sm font-semibold text-green-800">
                            Spreadsheet K3-02 siap digunakan.
                        </p>
                        <a
                            href="{{ session('generated_k302_url') }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-3 inline-flex rounded-lg bg-green-600
                                   px-4 py-2 text-sm font-semibold text-white
                                   hover:bg-green-700"
                        >
                            Buka Spreadsheet Hasil
                        </a>
                    </div>
                @endif

                @if($k302DailyFiles->isNotEmpty())
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">
                                        Tanggal
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">
                                        Nama Spreadsheet
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">
                                        Terakhir Sinkron
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($k302DailyFiles as $dailyFile)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ $dailyFile->workspace_date?->format('d-m-Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                            {{ $dailyFile->spreadsheet_name }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                            {{ $dailyFile->last_synced_at?->format('d-m-Y H:i') ?? '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a
                                                href="{{ $dailyFile->spreadsheet_url }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                            >
                                                Buka
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <p class="mt-4 text-xs text-gray-500">
                    Mode mingguan dan bulanan tetap menghasilkan satu file
                    terpisah untuk setiap tanggal. Jika suatu tanggal sudah
                    tersedia, SIMOLA tidak membuat duplikat; file lama akan
                    disinkronkan ulang pada folder tanggal yang sama.
                </p>
                    </div>
                </details>
            </section>

            <details
                data-simola-collapse="history"
                data-simola-force-open="{{
                    $historySummary['running'] > 0
                        ? 'true'
                        : 'false'
                }}"
                @if($historySummary['running'] > 0) open @endif
                class="simola-collapse overflow-hidden rounded-xl
                       border border-gray-200 bg-white shadow-sm"
            >
                <summary
                    class="flex cursor-pointer flex-col justify-between
                           gap-3 px-6 py-5
                           md:flex-row md:items-center"
                >
                    <div>
                        <p
                            class="text-xs font-bold uppercase
                                   tracking-wide text-gray-500"
                        >
                            Aktivitas Google Workspace
                        </p>
                        <h3
                            class="mt-1 text-lg font-bold text-gray-900"
                        >
                            Riwayat Proses
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Spreadsheet dan Evidence,
                            masing-masing 8 terbaru
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($historySummary['running'] > 0)
                            <span
                                class="rounded-full bg-blue-100
                                       px-3 py-1 text-xs font-bold
                                       text-blue-700"
                            >
                                {{
                                    number_format(
                                        $historySummary['running']
                                    )
                                }}
                                berjalan
                            </span>
                        @endif

                        @if($historySummary['attention_today'] > 0)
                            <span
                                class="rounded-full bg-orange-100
                                       px-3 py-1 text-xs font-bold
                                       text-orange-700"
                            >
                                {{
                                    number_format(
                                        $historySummary[
                                            'attention_today'
                                        ]
                                    )
                                }}
                                perlu perhatian
                            </span>
                        @endif

                        <span
                            class="simola-collapse-chevron ms-1
                                   text-xl text-gray-500"
                            aria-hidden="true"
                        >
                            ⌄
                        </span>
                    </div>
                </summary>

                <div
                    class="border-t border-gray-200
                           px-6 pb-6 pt-1"
                >
                <div
                    id="google-history-summary"
                    class="mt-5 grid gap-3 sm:grid-cols-2
                           xl:grid-cols-4"
                >
                    <div
                        class="rounded-xl border border-gray-200
                               bg-gray-50 p-4"
                    >
                        <p
                            class="text-xs font-bold uppercase
                                   text-gray-500"
                        >
                            Proses Hari Ini
                        </p>
                        <p
                            class="mt-2 text-2xl font-bold
                                   text-gray-900"
                        >
                            {{ number_format(
                                $historySummary['today']
                            ) }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-blue-200
                               bg-blue-50 p-4"
                    >
                        <p
                            class="text-xs font-bold uppercase
                                   text-blue-600"
                        >
                            Sedang Berjalan
                        </p>
                        <p
                            class="mt-2 text-2xl font-bold
                                   text-blue-900"
                        >
                            {{ number_format(
                                $historySummary['running']
                            ) }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-green-200
                               bg-green-50 p-4"
                    >
                        <p
                            class="text-xs font-bold uppercase
                                   text-green-600"
                        >
                            Berhasil Hari Ini
                        </p>
                        <p
                            class="mt-2 text-2xl font-bold
                                   text-green-900"
                        >
                            {{ number_format(
                                $historySummary['success_today']
                            ) }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-orange-200
                               bg-orange-50 p-4"
                    >
                        <p
                            class="text-xs font-bold uppercase
                                   text-orange-600"
                        >
                            Perlu Perhatian
                        </p>
                        <p
                            class="mt-2 text-2xl font-bold
                                   text-orange-900"
                        >
                            {{ number_format(
                                $historySummary['attention_today']
                            ) }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-2">
                    <div
                        class="overflow-hidden rounded-xl
                               border border-emerald-200"
                    >
                        <div
                            class="flex items-center justify-between gap-3
                                   border-b border-emerald-100
                                   bg-emerald-50 px-5 py-4"
                        >
                            <div>
                                <p
                                    class="text-xs font-bold uppercase
                                           tracking-wide text-emerald-600"
                                >
                                    Spreadsheet
                                </p>
                                <h4
                                    class="mt-1 font-bold text-gray-900"
                                >
                                    Sinkronisasi PC Set & K3-02
                                </h4>
                            </div>

                            <span
                                class="rounded-full bg-white px-3 py-1
                                       text-xs font-semibold
                                       text-emerald-700 shadow-sm"
                            >
                                8 terbaru
                            </span>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse($spreadsheetLogs as $log)
                                @php
                                    $processedItems =
                                        (int) $log->created_items
                                        + (int) $log->updated_items;

                                    $spreadsheetTypeLabel =
                                        match ($log->sync_type) {
                                            'pc_set_spreadsheet' =>
                                                'Sinkronisasi PC Set',

                                            'k302_daily_spreadsheet' =>
                                                'Spreadsheet K3-02',

                                            default =>
                                                $log->sync_type,
                                        };

                                    $statusClass =
                                        match ($log->status) {
                                            'success' =>
                                                'bg-green-100 text-green-700',

                                            'running' =>
                                                'bg-blue-100 text-blue-700',

                                            'partial' =>
                                                'bg-yellow-100 text-yellow-700',

                                            default =>
                                                'bg-red-100 text-red-700',
                                        };
                                @endphp

                                <article class="px-5 py-4">
                                    <div
                                        class="flex flex-col gap-3
                                               sm:flex-row
                                               sm:items-start
                                               sm:justify-between"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap
                                                       items-center gap-2"
                                            >
                                                <p
                                                    class="font-semibold
                                                           text-gray-900"
                                                >
                                                    {{ $spreadsheetTypeLabel }}
                                                </p>

                                                <span
                                                    class="rounded-full
                                                           px-2.5 py-1
                                                           text-[11px]
                                                           font-bold
                                                           {{ $statusClass }}"
                                                >
                                                    {{ strtoupper(
                                                        $log->status
                                                    ) }}
                                                </span>
                                            </div>

                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-500"
                                            >
                                                {{
                                                    $log->created_at
                                                        ?->format(
                                                            'd-m-Y H:i'
                                                        )
                                                }}

                                                @if($log->target_date)
                                                    <span
                                                        class="mx-1
                                                               text-gray-300"
                                                    >
                                                        •
                                                    </span>
                                                    Target
                                                    {{
                                                        $log->target_date
                                                            ->format(
                                                                'd-m-Y'
                                                            )
                                                    }}
                                                @endif
                                            </p>
                                        </div>

                                        <div
                                            class="shrink-0 text-left
                                                   sm:text-right"
                                        >
                                            <p
                                                class="text-sm font-bold
                                                       text-gray-900"
                                            >
                                                {{
                                                    number_format(
                                                        $processedItems
                                                    )
                                                }}
                                                /
                                                {{
                                                    number_format(
                                                        $log->total_items
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="text-xs
                                                       text-gray-500"
                                            >
                                                diproses
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-3 flex flex-wrap gap-2
                                               text-xs text-gray-600"
                                    >
                                        <span
                                            class="rounded-md bg-gray-100
                                                   px-2 py-1"
                                        >
                                            Dilewati:
                                            {{
                                                number_format(
                                                    $log->skipped_items
                                                )
                                            }}
                                        </span>

                                        @if($log->createdBy)
                                            <span
                                                class="rounded-md bg-gray-100
                                                       px-2 py-1"
                                            >
                                                Oleh:
                                                {{ $log->createdBy->name }}
                                            </span>
                                        @endif
                                    </div>

                                    @if(filled($log->message))
                                        <details
                                            class="mt-3 rounded-lg
                                                   border border-gray-200
                                                   bg-gray-50"
                                        >
                                            <summary
                                                class="cursor-pointer
                                                       px-3 py-2 text-xs
                                                       font-semibold
                                                       text-gray-600"
                                            >
                                                Lihat detail proses
                                            </summary>
                                            <p
                                                class="border-t
                                                       border-gray-200
                                                       px-3 py-3 text-sm
                                                       leading-6
                                                       text-gray-600"
                                            >
                                                {{ $log->message }}
                                            </p>
                                        </details>
                                    @endif
                                </article>
                            @empty
                                <div
                                    class="px-5 py-10 text-center
                                           text-sm text-gray-500"
                                >
                                    Belum ada riwayat Spreadsheet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div
                        id="evidence-history-card"
                        class="overflow-hidden rounded-xl
                               border border-blue-200"
                    >
                        <div
                            class="flex items-center justify-between gap-3
                                   border-b border-blue-100
                                   bg-blue-50 px-5 py-4"
                        >
                            <div>
                                <p
                                    class="text-xs font-bold uppercase
                                           tracking-wide text-blue-600"
                                >
                                    Evidence
                                </p>
                                <h4
                                    class="mt-1 font-bold text-gray-900"
                                >
                                    Pembuatan Struktur Folder
                                </h4>
                            </div>

                            <span
                                class="rounded-full bg-white px-3 py-1
                                       text-xs font-semibold
                                       text-blue-700 shadow-sm"
                            >
                                8 terbaru
                            </span>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse($evidenceLogs as $log)
                                @php
                                    $processedItems =
                                        (int) $log->created_items
                                        + (int) $log->updated_items;

                                    $completedItems =
                                        $processedItems
                                        + (int) $log->skipped_items
                                        + (int) $log->failed_items;

                                    $statusClass =
                                        match ($log->status) {
                                            'success' =>
                                                'bg-green-100 text-green-700',

                                            'running' =>
                                                'bg-blue-100 text-blue-700',

                                            'partial' =>
                                                'bg-yellow-100 text-yellow-700',

                                            default =>
                                                'bg-red-100 text-red-700',
                                        };

                                    $progressPercent =
                                        (int) $log->total_items > 0
                                            ? min(
                                                100,
                                                round(
                                                    (
                                                        $completedItems
                                                    )
                                                    / (int) $log
                                                        ->total_items
                                                    * 100
                                                )
                                            )
                                            : 0;
                                @endphp

                                <article
                                    class="px-5 py-4"
                                    @if($log->status === 'running')
                                        data-evidence-running="true"
                                    @endif
                                >
                                    <div
                                        class="flex flex-col gap-3
                                               sm:flex-row
                                               sm:items-start
                                               sm:justify-between"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap
                                                       items-center gap-2"
                                            >
                                                <p
                                                    class="font-semibold
                                                           text-gray-900"
                                                >
                                                    Folder Evidence
                                                </p>

                                                <span
                                                    class="rounded-full
                                                           px-2.5 py-1
                                                           text-[11px]
                                                           font-bold
                                                           {{ $statusClass }}"
                                                >
                                                    {{ strtoupper(
                                                        $log->status
                                                    ) }}
                                                </span>
                                            </div>

                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-500"
                                            >
                                                {{
                                                    $log->created_at
                                                        ?->format(
                                                            'd-m-Y H:i'
                                                        )
                                                }}

                                                @if($log->target_date)
                                                    <span
                                                        class="mx-1
                                                               text-gray-300"
                                                    >
                                                        •
                                                    </span>
                                                    Tanggal folder
                                                    {{
                                                        $log->target_date
                                                            ->format(
                                                                'd-m-Y'
                                                            )
                                                    }}
                                                @endif
                                            </p>
                                        </div>

                                        <div
                                            class="shrink-0 text-left
                                                   sm:text-right"
                                        >
                                            <p
                                                class="text-sm font-bold
                                                       text-gray-900"
                                            >
                                                {{ $progressPercent }}%
                                            </p>
                                            <p
                                                class="text-xs
                                                       text-gray-500"
                                            >
                                                selesai
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="relative mt-3 h-2
                                               overflow-hidden rounded-full
                                               bg-gray-100"
                                    >
                                        <div
                                            class="h-full rounded-full
                                                   transition-all
                                                   duration-500
                                            {{
                                                $log->status === 'failed'
                                                    ? 'bg-red-500'
                                                    : (
                                                        $log->status === 'partial'
                                                            ? 'bg-yellow-500'
                                                            : (
                                                                $log->status === 'running'
                                                                    ? 'bg-blue-500'
                                                                    : 'bg-green-500'
                                                            )
                                                    )
                                            }}"
                                            style="width: {{
                                                $progressPercent
                                            }}%"
                                        ></div>

                                        @if($log->status === 'running')
                                            <div
                                                class="evidence-progress-sweep
                                                       absolute inset-y-0
                                                       w-1/3 rounded-full
                                                       bg-blue-300/70"
                                            ></div>
                                        @endif
                                    </div>

                                    @if($log->status === 'running')
                                        <div
                                            class="mt-2 flex items-center
                                                   gap-2 text-xs
                                                   font-semibold
                                                   text-blue-700"
                                        >
                                            <span
                                                class="inline-block h-2 w-2
                                                       animate-pulse
                                                       rounded-full
                                                       bg-blue-500"
                                            ></span>

                                            <span>
                                                Sedang diproses:
                                                {{
                                                    number_format(
                                                        $completedItems
                                                    )
                                                }}
                                                dari
                                                {{
                                                    number_format(
                                                        $log->total_items
                                                    )
                                                }}
                                                kendaraan
                                            </span>
                                        </div>
                                    @endif

                                    <div
                                        class="mt-3 grid grid-cols-3 gap-2
                                               text-center text-xs"
                                    >
                                        <div
                                            class="rounded-lg bg-gray-50
                                                   px-2 py-2"
                                        >
                                            <p
                                                class="font-bold
                                                       text-gray-900"
                                            >
                                                {{
                                                    number_format(
                                                        $log->total_items
                                                    )
                                                }}
                                            </p>
                                            <p class="text-gray-500">
                                                Total
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg bg-green-50
                                                   px-2 py-2"
                                        >
                                            <p
                                                class="font-bold
                                                       text-green-800"
                                            >
                                                {{
                                                    number_format(
                                                        $processedItems
                                                    )
                                                }}
                                            </p>
                                            <p class="text-green-600">
                                                Dibuat
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg bg-gray-50
                                                   px-2 py-2"
                                        >
                                            <p
                                                class="font-bold
                                                       text-gray-900"
                                            >
                                                {{
                                                    number_format(
                                                        $log->skipped_items
                                                    )
                                                }}
                                            </p>
                                            <p class="text-gray-500">
                                                Dilewati
                                            </p>
                                        </div>
                                    </div>

                                    @if(filled($log->message))
                                        <details
                                            class="mt-3 rounded-lg
                                                   border border-gray-200
                                                   bg-gray-50"
                                        >
                                            <summary
                                                class="cursor-pointer
                                                       px-3 py-2 text-xs
                                                       font-semibold
                                                       text-gray-600"
                                            >
                                                Lihat detail proses
                                            </summary>
                                            <p
                                                class="border-t
                                                       border-gray-200
                                                       px-3 py-3 text-sm
                                                       leading-6
                                                       text-gray-600"
                                            >
                                                {{ $log->message }}
                                            </p>
                                        </details>
                                    @endif
                                </article>
                            @empty
                                <div
                                    class="px-5 py-10 text-center
                                           text-sm text-gray-500"
                                >
                                    Belum ada riwayat Evidence.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <p class="mt-5 text-xs leading-5 text-gray-500">
                    Riwayat lama tetap tersimpan di database, tetapi tidak
                    seluruhnya dimuat pada halaman ini sehingga tampilan
                    tetap ringan dan tidak menumpuk.
                </p>
                </div>
            </details>
        </div>
    </div>

<style>
    @keyframes evidence-progress-sweep {
        0% {
            transform: translateX(-130%);
        }

        100% {
            transform: translateX(330%);
        }
    }

    .evidence-progress-sweep {
        animation:
            evidence-progress-sweep
            1.4s
            ease-in-out
            infinite;
    }

    .simola-collapse > summary {
        list-style: none;
        user-select: none;
    }

    .simola-collapse > summary::-webkit-details-marker {
        display: none;
    }

    .simola-collapse-chevron {
        display: inline-block;
        transition: transform 180ms ease;
    }

    .simola-collapse[open] .simola-collapse-chevron {
        transform: rotate(180deg);
    }

    .simola-collapse > summary:hover {
        background-color: rgba(249, 250, 251, 0.7);
    }

</style>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        document
            .querySelectorAll(
                'details[data-simola-collapse]'
            )
            .forEach((panel) => {
                const panelName =
                    panel.dataset.simolaCollapse;

                const storageKey =
                    'simola-google-panel:'
                    + panelName;

                const forceOpen =
                    panel.dataset.simolaForceOpen
                    === 'true';

                if (forceOpen) {
                    panel.open = true;
                } else {
                    const savedState =
                        window.localStorage.getItem(
                            storageKey
                        );

                    if (savedState === 'open') {
                        panel.open = true;
                    }

                    if (savedState === 'closed') {
                        panel.open = false;
                    }
                }

                panel.addEventListener(
                    'toggle',
                    () => {
                        window.localStorage.setItem(
                            storageKey,
                            panel.open
                                ? 'open'
                                : 'closed'
                        );
                    }
                );
            });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const POLL_INTERVAL_MS = 10000;

        let pollingTimer = null;
        let pollingActive = false;

        const hasRunningEvidence = () => {
            return document.querySelector(
                '#evidence-history-card '
                + '[data-evidence-running="true"]'
            ) !== null;
        };

        const replaceSection = (
            incomingDocument,
            selector
        ) => {
            const current = document.querySelector(
                selector
            );

            const incoming =
                incomingDocument.querySelector(
                    selector
                );

            if (!current || !incoming) {
                return false;
            }

            current.replaceWith(
                incoming
            );

            return true;
        };

        const scheduleNextPoll = () => {
            window.clearTimeout(
                pollingTimer
            );

            if (!hasRunningEvidence()) {
                pollingActive = false;

                return;
            }

            pollingActive = true;

            pollingTimer = window.setTimeout(
                pollEvidenceProgress,
                POLL_INTERVAL_MS
            );
        };

        const pollEvidenceProgress = async () => {
            if (document.hidden) {
                scheduleNextPoll();

                return;
            }

            try {
                const response = await fetch(
                    window.location.href,
                    {
                        method: 'GET',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',

                            'Accept':
                                'text/html',
                        },

                        cache: 'no-store',
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        'Gagal membaca progres Evidence.'
                    );
                }

                const html =
                    await response.text();

                const incomingDocument =
                    new DOMParser()
                        .parseFromString(
                            html,
                            'text/html'
                        );

                replaceSection(
                    incomingDocument,
                    '#evidence-history-card'
                );

                replaceSection(
                    incomingDocument,
                    '#google-history-summary'
                );
            } catch (error) {
                console.warn(
                    'Live progress Evidence:',
                    error
                );
            } finally {
                scheduleNextPoll();
            }
        };

        document.addEventListener(
            'visibilitychange',
            () => {
                if (
                    !document.hidden
                    && hasRunningEvidence()
                    && !pollingActive
                ) {
                    scheduleNextPoll();
                }
            }
        );

        scheduleNextPoll();
    });
</script>

</x-app-layout>
