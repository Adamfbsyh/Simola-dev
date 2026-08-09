<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Compare Data Pengawas</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Upload Excel untuk dibandingkan dengan Master Fleet aktif. Upload tidak mengubah data operasional.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('master-fleet.import.index') }}"
                   class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Import Spreadsheet
                </a>
                <a href="{{ route('master-fleet.index') }}"
                   class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Master Fleet
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-6 grid gap-4 lg:grid-cols-[1.15fr_.85fr]">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="text-base font-semibold text-slate-900">Upload File Pengawas</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    XLSX, XLS, atau CSV. Header dikenali otomatis. Minimal tersedia NOPOL atau UNIT CODE.
                </p>

                <form method="POST" action="{{ route('master-fleet.compare.upload') }}"
                      enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-600">Jenis Armada</span>
                        <select name="fleet_type"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            @foreach ($fleetTypes as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('fleet_type', $currentFleetType) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-600">File Excel Pengawas</span>
                        <input type="file" name="spreadsheet" accept=".xlsx,.xls,.csv"
                               class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                               required>
                    </label>

                    <button type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Bandingkan dengan Master Aktif
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="text-sm font-semibold text-blue-900">Master Fleet tetap aman</div>
                <div class="mt-3 space-y-2 text-sm leading-6 text-blue-800">
                    <p>1. Upload hanya masuk staging.</p>
                    <p>2. SIMOLA menampilkan Sama, Berubah, Data Baru, Ganti Nopol, Tidak Ada di File Baru, dan Perlu Review.</p>
                    <p>3. Data baru/berubah baru diterapkan setelah Anda memilihnya.</p>
                    <p class="font-semibold">Data yang tidak ada di file baru tidak pernah dinonaktifkan otomatis.</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Riwayat Compare</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Waktu</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">File</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Armada</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ringkasan</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($batches as $batch)
                            @php($s = $batch->summary ?? [])
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $batch->created_at?->format('d/m/Y') }}
                                    <div class="text-xs text-slate-400">{{ $batch->created_at?->format('H:i') }}</div>
                                </td>
                                <td class="max-w-72 px-4 py-3">
                                    <div class="truncate font-semibold text-slate-900">{{ $batch->original_name }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $batch->sheet_name ?: '—' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">
                                        {{ \App\Support\MasterFleet\FleetType::label($batch->fleet_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs leading-5 text-slate-600">
                                    Sama {{ $s['same'] ?? 0 }} ·
                                    Berubah {{ ($s['changed'] ?? 0) + ($s['plate_change'] ?? 0) }} ·
                                    Baru {{ $s['new'] ?? 0 }} ·
                                    Review {{ $s['review'] ?? 0 }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ strtoupper(str_replace('_', ' ', $batch->status)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('master-fleet.compare.show', $batch) }}"
                                       class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                        Buka Compare
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada file pengawas yang dibandingkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 p-4">{{ $batches->links() }}</div>
        </div>
    </div>
</x-app-layout>
