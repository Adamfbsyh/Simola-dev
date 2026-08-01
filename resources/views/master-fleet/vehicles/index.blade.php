<x-app-layout>
    <x-slot name="header">
        <div
            class="flex flex-col justify-between gap-3
                   md:flex-row md:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Master Kendaraan
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Kelola nopol, perusahaan, status kendaraan,
                    dan histori perubahan nomor polisi.
                </p>
            </div>

            <div class="flex gap-2">
                <a
                    href="{{ route('master-fleet.index') }}"
                    class="rounded-lg border border-gray-300 bg-white
                           px-4 py-2 text-sm font-semibold text-gray-700"
                >
                    Kembali
                </a>

                @can('master-fleet.import')
                    <a
                        href="{{ route(
                            'master-fleet.vehicles.create'
                        ) }}"
                        class="rounded-lg bg-blue-600 px-4 py-2
                               text-sm font-bold text-white
                               hover:bg-blue-700"
                    >
                        + Tambah Kendaraan
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-[1600px] space-y-5
                   px-4 sm:px-6 lg:px-8"
        >
            @if(session('success'))
                <div
                    class="rounded-xl border border-green-200
                           bg-green-50 px-5 py-4 text-sm text-green-800"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4 text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            <section
                class="grid grid-cols-2 gap-4 lg:grid-cols-4"
            >
                <div
                    class="rounded-xl border border-gray-200
                           bg-white p-5 shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Total Kendaraan
                    </p>

                    <p class="mt-2 text-3xl font-bold">
                        {{ $totalCount }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-green-200
                           bg-green-50 p-5"
                >
                    <p class="text-xs font-semibold uppercase text-green-700">
                        Aktif
                    </p>

                    <p class="mt-2 text-3xl font-bold text-green-800">
                        {{ $activeCount }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 p-5"
                >
                    <p class="text-xs font-semibold uppercase text-red-700">
                        Nonaktif
                    </p>

                    <p class="mt-2 text-3xl font-bold text-red-800">
                        {{ $inactiveCount }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-blue-200
                           bg-blue-50 p-5"
                >
                    <p class="text-xs font-semibold uppercase text-blue-700">
                        Riwayat Nopol
                    </p>

                    <p class="mt-2 text-3xl font-bold text-blue-800">
                        {{ $historyCount }}
                    </p>
                </div>
            </section>

            <section
                class="rounded-xl border border-gray-200
                       bg-white p-5 shadow-sm"
            >
                <form
                    id="vehicle-filter-form"
                    method="GET"
                    action="{{ route(
                        'master-fleet.vehicles.index'
                    ) }}"
                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
                >
                    <div class="xl:col-span-2">
                        <label
                            for="vehicle-search"
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700"
                        >
                            Cari Kendaraan
                        </label>

                        <input
                            id="vehicle-search"
                            type="search"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Ketik nopol lama/baru, perusahaan, atau kode unit..."
                            autocomplete="off"
                            data-live-search
                            class="w-full rounded-lg border-gray-300
                                   shadow-sm"
                        >
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700"
                        >
                            Status
                        </label>

                        <select
                            name="status"
                            data-auto-submit
                            class="w-full rounded-lg border-gray-300"
                        >
                            <option value="">
                                Semua Status
                            </option>

                            <option
                                value="active"
                                @selected(
                                    $filters['status']
                                    === 'active'
                                )
                            >
                                Aktif
                            </option>

                            <option
                                value="inactive"
                                @selected(
                                    $filters['status']
                                    === 'inactive'
                                )
                            >
                                Nonaktif
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700"
                        >
                            Perusahaan
                        </label>

                        <select
                            name="company_id"
                            data-auto-submit
                            class="w-full rounded-lg border-gray-300"
                        >
                            <option value="">
                                Semua Perusahaan
                            </option>

                            @foreach($companies as $company)
                                <option
                                    value="{{ $company->id }}"
                                    @selected(
                                        (int)
                                        $filters['company_id']
                                        === $company->id
                                    )
                                >
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700"
                        >
                            Tampilkan
                        </label>

                        <select
                            name="per_page"
                            data-auto-submit
                            class="w-full rounded-lg border-gray-300"
                        >
                            @foreach([25, 50, 100] as $size)
                                <option
                                    value="{{ $size }}"
                                    @selected(
                                        (int)
                                        $filters['per_page']
                                        === $size
                                    )
                                >
                                    {{ $size }} data
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div
                        class="flex gap-2 md:col-span-2
                               xl:col-span-5 xl:justify-end"
                    >
                        <a
                            href="{{ route(
                                'master-fleet.vehicles.index'
                            ) }}"
                            class="rounded-lg border border-gray-300
                                   px-4 py-2 text-sm font-semibold"
                        >
                            Reset
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2
                                   text-sm font-bold text-white"
                        >
                            Terapkan
                        </button>
                    </div>
                </form>
            </section>

            <section
                class="overflow-hidden rounded-xl border
                       border-gray-200 bg-white shadow-sm"
            >
                <div class="border-b px-5 py-4">
                    <h3 class="text-lg font-bold text-gray-900">
                        Daftar Kendaraan
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Menampilkan
                        {{ $vehicles->firstItem() ?? 0 }}
                        sampai
                        {{ $vehicles->lastItem() ?? 0 }}
                        dari
                        {{ $vehicles->total() }}
                        kendaraan.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    Nopol
                                </th>

                                <th class="px-4 py-3 text-left">
                                    Perusahaan
                                </th>

                                <th class="px-4 py-3 text-left">
                                    Kode Unit
                                </th>

                                <th class="px-4 py-3 text-center">
                                    PC Aktif
                                </th>

                                <th class="px-4 py-3 text-center">
                                    PC Draft
                                </th>

                                <th class="px-4 py-3 text-center">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-center">
                                    Riwayat
                                </th>

                                <th class="px-4 py-3 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($vehicles as $vehicle)
                                @php
                                    $publishedAssignment =
                                        $publishedAssignments[
                                            $vehicle->id
                                        ]
                                        ?? null;

                                    $draftAssignment =
                                        $draftAssignments[
                                            $vehicle->id
                                        ]
                                        ?? null;
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td
                                        class="whitespace-nowrap px-4 py-3
                                               font-bold text-gray-900"
                                    >
                                        {{ $vehicle->plate_number }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{
                                            $vehicle->company?->name
                                            ?? 'Belum tersedia'
                                        }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $vehicle->unit_code ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        @if($publishedAssignment?->pc_final)
                                            <span
                                                class="rounded-full bg-blue-600
                                                       px-3 py-1 text-xs
                                                       font-bold text-white"
                                            >
                                                PC
                                                {{
                                                    $publishedAssignment
                                                        ->pc_final
                                                }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        @if($draftAssignment?->pc_final)
                                            <span
                                                class="rounded-full bg-purple-100
                                                       px-3 py-1 text-xs
                                                       font-bold text-purple-700"
                                            >
                                                PC
                                                {{
                                                    $draftAssignment
                                                        ->pc_final
                                                }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="rounded-full px-3 py-1
                                                   text-xs font-bold
                                                   {{
                                                       $vehicle->is_active
                                                           ? 'bg-green-100 text-green-700'
                                                           : 'bg-red-100 text-red-700'
                                                   }}"
                                        >
                                            {{
                                                $vehicle->is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'
                                            }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <a
                                            href="{{ route(
                                                'master-fleet.vehicles.history',
                                                $vehicle
                                            ) }}"
                                            class="font-semibold text-blue-600"
                                        >
                                            {{
                                                $vehicle
                                                    ->plate_histories_count
                                            }}
                                            perubahan
                                        </a>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div
                                            class="flex justify-end gap-2"
                                        >
                                            @can('master-fleet.import')
                                                <a
                                                    href="{{ route(
                                                        'master-fleet.vehicles.edit',
                                                        $vehicle
                                                    ) }}"
                                                    class="rounded-lg border
                                                           border-gray-300
                                                           px-3 py-1.5
                                                           text-xs font-bold
                                                           text-gray-700"
                                                >
                                                    Edit
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'master-fleet.vehicles.toggle-active',
                                                        $vehicle
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        onclick="
                                                            return confirm(
                                                                'Ubah status kendaraan ini?'
                                                            );
                                                        "
                                                        class="rounded-lg
                                                               px-3 py-1.5
                                                               text-xs font-bold
                                                               {{
                                                                   $vehicle
                                                                       ->is_active
                                                                       ? 'bg-red-50 text-red-700'
                                                                       : 'bg-green-50 text-green-700'
                                                               }}"
                                                    >
                                                        {{
                                                            $vehicle
                                                                ->is_active
                                                                ? 'Nonaktifkan'
                                                                : 'Aktifkan'
                                                        }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="8"
                                        class="px-5 py-12 text-center
                                               text-gray-500"
                                    >
                                        Data kendaraan tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($vehicles->hasPages())
                    <div class="border-t px-5 py-4">
                        {{ $vehicles->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document
                .querySelectorAll('[data-live-search]')
                .forEach(function (input) {
                    let timer = null;

                    input.addEventListener('input', function () {
                        window.clearTimeout(timer);

                        timer = window.setTimeout(function () {
                            if (input.form) {
                                input.form.requestSubmit();
                            }
                        }, 450);
                    });
                });

            document
                .querySelectorAll('[data-auto-submit]')
                .forEach(function (select) {
                    select.addEventListener('change', function () {
                        if (select.form) {
                            select.form.requestSubmit();
                        }
                    });
                });
        });
    </script>
</x-app-layout>