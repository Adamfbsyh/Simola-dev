<x-app-layout>
    @php
        $labels = [
            '' => 'Semua',
            'applyable' => 'Bisa Diterapkan',
            'changed' => 'Berubah',
            'plate_change' => 'Ganti Nopol',
            'new' => 'Data Baru',
            'missing' => 'Tidak Ada di File',
            'review' => 'Perlu Review',
            'same' => 'Sama',
        ];

        $badges = [
            'same' => 'bg-slate-100 text-slate-700',
            'changed' => 'bg-blue-50 text-blue-700',
            'plate_change' => 'bg-violet-50 text-violet-700',
            'new' => 'bg-emerald-50 text-emerald-700',
            'missing' => 'bg-amber-50 text-amber-700',
            'review' => 'bg-red-50 text-red-700',
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-semibold text-slate-900">Hasil Compare</h1>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        {{ \App\Support\MasterFleet\FleetType::label($batch->fleet_type) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $batch->original_name }} · Sheet {{ $batch->sheet_name ?: '—' }} · Header baris {{ $batch->header_row ?: '—' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('master-fleet.compare.download', $batch) }}"
                   class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    File Asli
                </a>
                <a href="{{ route('master-fleet.compare.export', $batch) }}"
                   class="inline-flex h-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    Export Compare
                </a>
                <a href="{{ route('master-fleet.compare.index') }}"
                   class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Compare Lain
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

        <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Excel</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['total_source'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Sama</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['same'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Berubah</div>
                <div class="mt-2 text-2xl font-semibold text-blue-800">{{ number_format(($summary['changed'] ?? 0) + ($summary['plate_change'] ?? 0)) }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Data Baru</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format($summary['new'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Tidak Ada di File</div>
                <div class="mt-2 text-2xl font-semibold text-amber-800">{{ number_format($summary['missing'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-red-700">Perlu Review</div>
                <div class="mt-2 text-2xl font-semibold text-red-800">{{ number_format($summary['review'] ?? 0) }}</div>
            </div>
        </div>

        <div class="mb-5 rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap gap-2">
                @foreach ($labels as $value => $label)
                    <a href="{{ route('master-fleet.compare.show', ['batch' => $batch, 'status' => $value]) }}"
                       class="rounded-lg border px-3 py-2 text-xs font-semibold {{ $status === $value ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('master-fleet.compare.apply', $batch) }}">
            @csrf

            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">
                    Centang hanya data yang sudah direview. “Tidak Ada di File” tidak bisa diterapkan otomatis.
                </div>

                <div class="flex gap-2">
                    <button type="button" id="select-applyable"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Pilih Semua di Halaman
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Terapkan Perubahan Terpilih
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-12 px-3 py-3 text-center font-semibold text-slate-600">Pilih</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Baris</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Nopol / Unit</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Perbedaan</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Apply</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                @php
                                    $label = $labels[$row->status] ?? strtoupper($row->status);
                                    $badge = $badges[$row->status] ?? 'bg-slate-100 text-slate-700';
                                @endphp

                                <tr class="align-top">
                                    <td class="px-3 py-3 text-center">
                                        @if ($row->can_apply && $row->apply_status === 'pending')
                                            <input type="checkbox" name="rows[]" value="{{ $row->id }}"
                                                   class="apply-row rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $badge }}">{{ $label }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $row->source_row ?: '—' }}</td>
                                    <td class="min-w-44 px-3 py-3">
                                        <div class="font-semibold text-slate-900">{{ $row->plate_number ?: '—' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Unit: {{ $row->unit_code ?: '—' }}</div>
                                    </td>
                                    <td class="min-w-[360px] px-3 py-3">
                                        @if ($row->diff_data)
                                            <div class="space-y-2">
                                                @foreach ($row->diff_data as $field => $change)
                                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                                                        <div class="text-xs font-semibold text-slate-600">{{ $field }}</div>
                                                        <div class="mt-1 grid gap-1 text-xs sm:grid-cols-2">
                                                            <div>
                                                                <span class="text-slate-400">Master:</span>
                                                                <span class="font-medium text-slate-700">
                                                                    {{ is_bool($change['before'] ?? null) ? (($change['before'] ?? false) ? 'Aktif' : 'Nonaktif') : ((($change['before'] ?? null) === null || ($change['before'] ?? '') === '') ? '—' : $change['before']) }}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <span class="text-slate-400">Pengawas:</span>
                                                                <span class="font-medium text-indigo-700">
                                                                    {{ is_bool($change['after'] ?? null) ? (($change['after'] ?? false) ? 'Aktif' : 'Nonaktif') : ((($change['after'] ?? null) === null || ($change['after'] ?? '') === '') ? '—' : $change['after']) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">Tidak ada perbedaan.</span>
                                        @endif
                                    </td>
                                    <td class="min-w-44 px-3 py-3">
                                        @if ($row->apply_status === 'applied')
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Sudah diterapkan</span>
                                        @elseif ($row->can_apply)
                                            <span class="text-xs font-medium text-slate-600">Siap dipilih</span>
                                        @else
                                            <span class="text-xs text-slate-500">{{ $row->apply_message ?: 'Review saja' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">Tidak ada data pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 p-4">{{ $rows->links() }}</div>
            </div>
        </form>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">
            “Tidak Ada di File Baru” hanya berarti kendaraan ada di Master Fleet tetapi tidak ditemukan pada file ini.
            SIMOLA tidak menghapus atau menonaktifkannya otomatis.
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('select-applyable');
            if (!button) return;

            button.addEventListener('click', function () {
                const boxes = Array.from(document.querySelectorAll('.apply-row'));
                const allChecked = boxes.length > 0 && boxes.every((box) => box.checked);
                boxes.forEach((box) => box.checked = !allChecked);
                button.textContent = allChecked ? 'Pilih Semua di Halaman' : 'Batalkan Pilihan Halaman';
            });
        });
    </script>
</x-app-layout>
