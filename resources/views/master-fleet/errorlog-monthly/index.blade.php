<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">
                    Generator Error Log Bulanan
                </h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    1 spreadsheet per bulan · OAuth K3-02 · anti-duplikat root + bulan
                </p>
            </div>

            <a
                href="{{ route('master-fleet.google-workspace.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300
                       bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Google Workspace
            </a>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-5xl space-y-3 px-4 sm:px-6 lg:px-8">
            @php
                /*
                 * Halaman ini sendiri sudah berada di route
                 * auth + verified + can:master-fleet.view.
                 * Tidak perlu melakukan pemeriksaan permission kedua
                 * yang sebelumnya menyebabkan tombol tersembunyi.
                 */
                $errorlogCanGenerate = true;
            @endphp
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc space-y-1 ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="grid gap-3 border-b border-gray-100 px-4 py-3 sm:grid-cols-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            OAuth K3-02
                        </div>
                        @if($k302Account)
                            <div class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-green-700">
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                Terhubung
                            </div>
                            <div class="mt-0.5 truncate text-xs text-gray-500" title="{{ $k302Account->google_email }}">
                                {{ $k302Account->google_email }}
                            </div>
                        @else
                            <div class="mt-1 text-sm font-semibold text-amber-700">Belum terhubung</div>
                            @if($errorlogCanGenerate)
                                <a
                                    href="{{ route('master-fleet.google-workspace.connect', ['purpose' => 'k302']) }}"
                                    class="mt-1 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-800"
                                >
                                    Hubungkan K3-02 →
                                </a>
                            @endif
                        @endif
                    </div>

                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Template
                        </div>
                        <div class="mt-1 text-sm font-semibold text-gray-800">Error Log Bulanan</div>
                        @if($templateUrl)
                            <a
                                href="{{ $templateUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-0.5 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-800"
                            >
                                Buka template ↗
                            </a>
                        @endif
                    </div>

                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Periode template
                        </div>
                        <div class="mt-1 text-sm font-semibold text-gray-800">{{ $periodCell }}</div>
                        <div class="mt-0.5 text-xs text-gray-500">Otomatis diisi hari pertama bulan terpilih.</div>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('master-fleet.errorlog-monthly.store') }}"
                    class="px-4 py-4"
                >
                    @csrf

                    <div class="grid gap-3 md:grid-cols-[160px_minmax(0,1fr)_auto] md:items-end">
                        <div>
                            <label for="month" class="mb-1 block text-xs font-semibold text-gray-700">
                                Bulan
                            </label>
                            <input
                                id="month"
                                name="month"
                                type="month"
                                value="{{ old('month', $defaultMonth) }}"
                                required
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label for="root_folder" class="mb-1 block text-xs font-semibold text-gray-700">
                                Root Folder Google Drive
                            </label>
                            <input
                                id="root_folder"
                                name="root_folder"
                                type="text"
                                value="{{ old('root_folder', $rootFolderId) }}"
                                required
                                autocomplete="off"
                                placeholder="Folder ID atau URL Google Drive"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        @if($errorlogCanGenerate)
                            <button
                                id="errorlog-generate-button"
                                type="submit"
                                @disabled(!$k302Account)
                                class="inline-flex h-[42px] items-center justify-center rounded-lg px-4 text-sm font-bold
                                       {{ $k302Account
                                            ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                            : 'cursor-not-allowed bg-gray-200 text-gray-500' }}"
                            >
                                Generate Bulan
                            </button>
                        @endif
                    </div>

                    <div
                        id="errorlog-monthly-status"
                        class="mt-3 flex min-h-[42px] flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2
                               sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span id="errorlog-status-dot" class="h-2 w-2 shrink-0 rounded-full bg-gray-300"></span>
                                <span id="errorlog-status-label" class="text-xs font-bold text-gray-700">
                                    Status file bulan
                                </span>
                            </div>
                            <div id="errorlog-status-message" class="mt-0.5 truncate text-[11px] text-gray-500">
                                {{ $k302Account ? 'Memeriksa root + bulan…' : 'Hubungkan OAuth K3-02 untuk memeriksa file.' }}
                            </div>
                        </div>

                        <a
                            id="errorlog-status-open"
                            href="#"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hidden shrink-0 items-center justify-center rounded-lg border border-green-200 bg-green-50
                                   px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-100"
                        >
                            Buka Spreadsheet ↗
                        </a>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500">
                        <span>• File memakai struktur root / tahun / bulan (contoh: 2026 / AGUSTUS).</span>
                        <span>• Key duplikat: root folder + YYYY-MM.</span>
                        <span>• Generate ulang memakai file yang sama dan menyinkronkan {{ $periodCell }}.</span>
                    </div>
                </form>
            </section>

            @php($generated = session('generated_errorlog'))
            @if(is_array($generated))
                <section class="rounded-xl border border-indigo-200 bg-indigo-50/60 px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-indigo-500">
                                Hasil terakhir
                            </div>
                            <div class="mt-1 truncate text-sm font-bold text-gray-900">
                                {{ $generated['spreadsheet_name'] ?? 'Spreadsheet Error Log' }}
                            </div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-600">
                                <span>{{ $generated['month_label'] ?? ($generated['month'] ?? '-') }}</span>
                                <span>{{ $generated['root_folder_name'] ?? 'Root Drive' }}</span>
                                <span>
                                    {{ ($generated['period_cell'] ?? $periodCell) }} =
                                    {{ $generated['period_value'] ?? '-' }}
                                </span>
                            </div>
                        </div>

                        @if(!empty($generated['spreadsheet_url']))
                            <a
                                href="{{ $generated['spreadsheet_url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600
                                       px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700"
                            >
                                Buka Spreadsheet ↗
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <div>
                        <div class="text-sm font-bold text-gray-900">Aktivitas Generator</div>
                        <div class="mt-0.5 text-[11px] text-gray-500">Riwayat create, reuse, dan gagal terbaru.</div>
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">v1.1.4</div>
                </div>

                @if(empty($recentActivities))
                    <div class="px-4 py-5 text-center text-xs text-gray-500">
                        Belum ada aktivitas yang tercatat. Aktivitas mulai tercatat setelah upgrade v1.1.
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($recentActivities as $activity)
                            @php($action = (string) ($activity['action'] ?? ''))
                            @php($isFailed = $action === 'failed')
                            @php($isCreated = $action === 'created')
                            <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold
                                            {{ $isFailed
                                                ? 'bg-red-50 text-red-700'
                                                : ($isCreated ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700') }}">
                                            {{ $isFailed ? 'GAGAL' : ($isCreated ? 'DIBUAT' : 'SUDAH ADA') }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-800">
                                            {{ $activity['month_label'] ?? ($activity['month'] ?? '-') }}
                                        </span>
                                        <span class="text-[11px] text-gray-400">{{ $activity['occurred_label'] ?? '-' }}</span>
                                    </div>

                                    <div class="mt-1 truncate text-xs text-gray-600">
                                        {{ $activity['spreadsheet_name'] ?: ($activity['root_folder_name'] ?? 'Root Drive') }}
                                    </div>
                                    <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[10px] text-gray-400">
                                        <span>{{ $activity['user_name'] ?? 'User SIMOLA' }}</span>
                                        @if(!empty($activity['root_folder_name']) && !empty($activity['spreadsheet_name']))
                                            <span>{{ $activity['root_folder_name'] }}</span>
                                        @endif
                                        @if($isFailed && !empty($activity['message']))
                                            <span class="text-red-500" title="{{ $activity['message'] }}">
                                                {{ \Illuminate\Support\Str::limit((string) $activity['message'], 100) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if(!empty($activity['spreadsheet_url']))
                                    <a
                                        href="{{ $activity['spreadsheet_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-200
                                               bg-white px-3 py-1.5 text-[11px] font-bold text-gray-700 hover:bg-gray-50"
                                    >
                                        Buka ↗
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const enabled = @json((bool) $k302Account);
            const statusUrl = @json(route('master-fleet.errorlog-monthly.status'));
            const monthInput = document.getElementById('month');
            const rootInput = document.getElementById('root_folder');
            const dot = document.getElementById('errorlog-status-dot');
            const label = document.getElementById('errorlog-status-label');
            const message = document.getElementById('errorlog-status-message');
            const openButton = document.getElementById('errorlog-status-open');
            let debounceTimer = null;
            let requestController = null;

            if (!enabled || !monthInput || !rootInput || !dot || !label || !message || !openButton) {
                return;
            }

            const hideOpenButton = () => {
                openButton.classList.add('hidden');
                openButton.classList.remove('inline-flex');
                openButton.removeAttribute('href');
            };

            const setState = (state, text, detail, url = '') => {
                dot.className = 'h-2 w-2 shrink-0 rounded-full';
                label.className = 'text-xs font-bold';
                hideOpenButton();

                if (state === 'exists') {
                    dot.classList.add('bg-green-500');
                    label.classList.add('text-green-700');
                    label.textContent = 'Sudah ada';
                } else if (state === 'missing') {
                    dot.classList.add('bg-indigo-400');
                    label.classList.add('text-indigo-700');
                    label.textContent = 'Belum dibuat';
                } else if (state === 'error') {
                    dot.classList.add('bg-red-500');
                    label.classList.add('text-red-700');
                    label.textContent = 'Tidak dapat diperiksa';
                } else {
                    dot.classList.add('bg-gray-300');
                    label.classList.add('text-gray-700');
                    label.textContent = text || 'Memeriksa…';
                }

                message.textContent = detail || '';

                if (url) {
                    openButton.href = url;
                    openButton.classList.remove('hidden');
                    openButton.classList.add('inline-flex');
                }
            };

            const checkStatus = async () => {
                const month = monthInput.value.trim();
                const root = rootInput.value.trim();

                if (!month || !root) {
                    setState('idle', 'Status file bulan', 'Isi bulan dan root folder untuk memeriksa file.');
                    return;
                }

                if (requestController) {
                    requestController.abort();
                }
                requestController = new AbortController();
                setState('idle', 'Memeriksa…', 'Mengecek kombinasi root + bulan di Google Drive…');

                const params = new URLSearchParams({
                    month: month,
                    root_folder: root,
                });

                try {
                    const response = await fetch(statusUrl + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: requestController.signal,
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.message || 'Status file tidak dapat diperiksa.');
                    }

                    const data = payload.data || {};
                    if (payload.exists) {
                        const detail = (data.spreadsheet_name || 'Spreadsheet Error Log')
                            + (data.root_folder_name ? ' · ' + data.root_folder_name : '');
                        setState('exists', '', detail, data.spreadsheet_url || '');
                    } else {
                        const detail = (data.expected_name || 'Spreadsheet Error Log')
                            + (data.root_folder_name ? ' · ' + data.root_folder_name : '');
                        setState('missing', '', detail);
                    }
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    setState('error', '', error && error.message ? error.message : 'Status file tidak dapat diperiksa.');
                }
            };

            const scheduleCheck = () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(checkStatus, 450);
            };

            monthInput.addEventListener('change', scheduleCheck);
            rootInput.addEventListener('input', scheduleCheck);
            checkStatus();
        });
    </script>
</x-app-layout>
