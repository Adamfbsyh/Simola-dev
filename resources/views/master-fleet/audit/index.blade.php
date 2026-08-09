<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">
                    Riwayat Perubahan Master Fleet
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Audit perubahan Master Kendaraan, grouping, import, dan master referensi.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('master-fleet.index') }}"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Kembali ke Master Fleet
                </a>

                <a
                    href="{{ route('master-fleet.audit.export', request()->query()) }}"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                    Export Excel
                </a>
            </div>
        </div>

        <div class="mb-5 rounded-xl border border-slate-200 bg-white p-4">
            <form
                method="GET"
                action="{{ route('master-fleet.audit.index') }}"
                class="grid gap-3 md:grid-cols-2 xl:grid-cols-6"
            >
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">
                        Periode
                    </span>

                    <input
                        type="month"
                        name="period"
                        value="{{ $filters['period'] }}"
                        class="block w-full min-w-0 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">
                        Armada
                    </span>

                    <select
                        name="fleet_type"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            Semua
                        </option>

                        @foreach ($fleetTypes as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($filters['fleet_type'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">
                        Modul
                    </span>

                    <select
                        name="module"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            Semua modul
                        </option>

                        @foreach ($modules as $module)
                            <option
                                value="{{ $module }}"
                                @selected($filters['module'] === $module)
                            >
                                {{ $module }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">
                        Aksi
                    </span>

                    <select
                        name="action"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            Semua aksi
                        </option>

                        @foreach ($actions as $action)
                            <option
                                value="{{ $action }}"
                                @selected($filters['action'] === $action)
                            >
                                {{ $action }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block xl:col-span-2">
                    <span class="mb-1 block text-xs font-medium text-slate-600">
                        Cari Data / Pengguna
                    </span>

                    <div class="flex gap-2">
                        <input
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Nopol, nama, pengguna..."
                            class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >

                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Tampilkan
                        </button>

                        <a
                            href="{{ route('master-fleet.audit.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </label>
            </form>

            <p class="mt-3 text-xs leading-5 text-slate-500">
                Catatan: perubahan TLPG/Terminal, SPBE/Perusahaan, dan referensi bersama tetap muncul saat
                <span class="font-semibold text-slate-700">Armada = Semua</span>.
                Untuk melihatnya secara khusus, gunakan filter
                <span class="font-semibold text-slate-700">Modul</span>.
            </p>
        </div>

        <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Total Perubahan
                </div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ number_format($summary['total']) }}
                </div>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                    Kendaraan
                </div>
                <div class="mt-2 text-2xl font-semibold text-blue-800">
                    {{ number_format($summary['vehicle']) }}
                </div>
            </div>

            <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-violet-700">
                    Grouping
                </div>
                <div class="mt-2 text-2xl font-semibold text-violet-800">
                    {{ number_format($summary['grouping']) }}
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                    Master Data
                </div>
                <div class="mt-2 text-2xl font-semibold text-amber-800">
                    {{ number_format($summary['master_data']) }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                    Import
                </div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800">
                    {{ number_format($summary['import']) }}
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="text-sm font-semibold text-slate-900">
                    Detail Perubahan
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Klik “Lihat detail” untuk melihat snapshot sebelum dan sesudah.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Waktu
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Armada
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Modul / Aksi
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Data
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Oleh
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Detail
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($audits as $audit)
                            @php
                                $fleetTypeLabel =
                                    match ($audit->fleet_type) {
                                        \App\Support\MasterFleet\FleetType::LPG => 'MT LPG',
                                        \App\Support\MasterFleet\FleetType::PERTASHOP => 'MT PERTASHOP',
                                        'SHARED' => 'REFERENSI BERSAMA',
                                        default => $audit->fleet_type ?: '—',
                                    };
                            @endphp

                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ optional($audit->occurred_at)->format('d/m/Y') }}
                                    <div class="text-xs text-slate-400">
                                        {{ optional($audit->occurred_at)->format('H:i:s') }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ $fleetTypeLabel }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">
                                        {{ $audit->module }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $audit->action }}
                                    </div>
                                </td>

                                <td class="min-w-56 px-4 py-3">
                                    <div class="font-semibold text-slate-900">
                                        {{ $audit->subject_label ?: '—' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $audit->description }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ $audit->user_name ?: 'System' }}
                                    </div>

                                    <div class="text-xs text-slate-400">
                                        {{ $audit->user_email ?: '—' }}
                                    </div>
                                </td>

                                <td class="min-w-80 px-4 py-3">
                                    <details class="group">
                                        <summary class="cursor-pointer text-sm font-semibold text-indigo-600">
                                            Lihat detail
                                        </summary>

                                        <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Sebelum
                                                </div>

                                                <pre class="whitespace-pre-wrap break-words text-xs text-slate-700">{{ $audit->before_data ? json_encode($audit->before_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'Tidak ada snapshot sebelum.' }}</pre>
                                            </div>

                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Sesudah
                                                </div>

                                                <pre class="whitespace-pre-wrap break-words text-xs text-slate-700">{{ $audit->after_data ? json_encode($audit->after_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'Tidak ada snapshot sesudah / aksi ringkasan.' }}</pre>
                                            </div>
                                        </div>

                                        @if ($audit->meta)
                                            <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
                                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Parameter Aksi
                                                </div>

                                                <pre class="whitespace-pre-wrap break-words text-xs text-slate-700">{{ json_encode($audit->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center text-slate-500"
                                >
                                    Belum ada riwayat perubahan pada filter/periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 p-4">
                {{ $audits->links() }}
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">
            Audit otomatis mulai mencatat perubahan setelah fitur ini dipasang. Data historis sebelum pemasangan tetap tersedia melalui histori lama masing-masing modul, tetapi tidak direkonstruksi agar laporan audit tidak mengandung asumsi.
        </div>
    </div>
</x-app-layout>
