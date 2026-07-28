<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Master SPBE / Perusahaan
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Kelola SPBE, pasangan TLPG,
                    koordinat, jarak, dan bobot.
                </p>
            </div>

            @can('fleet-company.create')
                <a
                    href="{{ route(
                        'master-fleet.companies.create'
                    ) }}"
                    class="rounded-lg bg-blue-600 px-4 py-2
                           text-sm font-semibold text-white"
                >
                    Tambah SPBE
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200
                            bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200
                            bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <form
                method="GET"
                class="mb-5 grid gap-3 rounded-xl border
                       bg-white p-4 shadow-sm lg:grid-cols-5"
            >
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari SPBE..."
                    class="rounded-lg border-gray-300 lg:col-span-2"
                >

                <select
                    name="terminal_id"
                    class="rounded-lg border-gray-300"
                >
                    <option value="">
                        Semua TLPG
                    </option>

                    @foreach($terminals as $terminal)
                        <option
                            value="{{ $terminal->id }}"
                            @selected(
                                (string) $terminalId
                                ===
                                (string) $terminal->id
                            )
                        >
                            {{ $terminal->name }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="status"
                    class="rounded-lg border-gray-300"
                >
                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="active"
                        @selected($status === 'active')
                    >
                        Aktif
                    </option>

                    <option
                        value="inactive"
                        @selected($status === 'inactive')
                    >
                        Nonaktif
                    </option>
                </select>

                <div class="flex gap-2">
                    <button
                        class="flex-1 rounded-lg bg-gray-900
                               px-4 py-2 text-sm font-semibold text-white"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route(
                            'master-fleet.companies.index'
                        ) }}"
                        class="rounded-lg border px-4 py-2
                               text-sm font-semibold text-gray-700"
                    >
                        Reset
                    </a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border
                        bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-500">
                                <th class="px-5 py-3">SPBE</th>
                                <th class="px-5 py-3">TLPG</th>
                                <th class="px-5 py-3">Jarak</th>
                                <th class="px-5 py-3">Kategori</th>
                                <th class="px-5 py-3">Bobot</th>
                                <th class="px-5 py-3">Kendaraan</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse($companies as $company)
                                @php
                                    $profile =
                                        $company
                                            ->distanceProfiles
                                            ->firstWhere(
                                                'terminal_id',
                                                $company
                                                    ->default_terminal_id
                                            );
                                @endphp

                                <tr class="text-sm">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-gray-900">
                                            {{ $company->name }}
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            {{ $company->code ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        {{
                                            $company
                                                ->defaultTerminal
                                                ?->name
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-4">
                                        {{
                                            $profile?->distance_km
                                                ? $profile->distance_km
                                                    . ' km'
                                                : '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-4 capitalize">
                                        {{
                                            $profile
                                                ?->distance_category
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-4">
                                        {{ $profile?->weight ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4">
                                        {{ $company->vehicles_count }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1
                                                   text-xs font-semibold
                                                   {{
                                                       $company->is_active
                                                           ? 'bg-green-100 text-green-700'
                                                           : 'bg-gray-100 text-gray-600'
                                                   }}"
                                        >
                                            {{
                                                $company->is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'
                                            }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            @can('fleet-company.update')
                                                <a
                                                    href="{{ route(
                                                        'master-fleet.companies.edit',
                                                        $company
                                                    ) }}"
                                                    class="rounded-lg border
                                                           px-3 py-1.5 text-xs
                                                           font-semibold text-blue-700"
                                                >
                                                    Edit
                                                </a>
                                            @endcan

                                            @can('fleet-company.disable')
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'master-fleet.companies.toggle-active',
                                                        $company
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        class="rounded-lg border
                                                               px-3 py-1.5 text-xs
                                                               font-semibold
                                                               {{
                                                                   $company->is_active
                                                                       ? 'text-red-700'
                                                                       : 'text-green-700'
                                                               }}"
                                                    >
                                                        {{
                                                            $company->is_active
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
                                        class="px-5 py-10 text-center
                                               text-sm text-gray-500"
                                    >
                                        Belum ada data SPBE.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t px-5 py-4">
                    {{ $companies->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>