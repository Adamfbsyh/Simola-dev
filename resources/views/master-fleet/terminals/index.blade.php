<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Master TLPG / Terminal
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Kelola depo tujuan dan koordinat lokasi.
                </p>
            </div>

            @can('fleet-terminal.create')
                <a
                    href="{{ route(
                        'master-fleet.terminals.create'
                    ) }}"
                    class="rounded-lg bg-blue-600 px-4 py-2
                           text-sm font-semibold text-white
                           hover:bg-blue-700"
                >
                    Tambah TLPG
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
                       bg-white p-4 shadow-sm md:grid-cols-4"
            >
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama atau kode..."
                    class="rounded-lg border-gray-300 md:col-span-2"
                >

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
                            'master-fleet.terminals.index'
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
                                <th class="px-5 py-3">TLPG</th>
                                <th class="px-5 py-3">Koordinat</th>
                                <th class="px-5 py-3">SPBE</th>
                                <th class="px-5 py-3">Profil Jarak</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse($terminals as $terminal)
                                <tr class="text-sm">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-gray-900">
                                            {{ $terminal->name }}
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            {{ $terminal->code ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-gray-600">
                                        @if(
                                            $terminal->latitude
                                            &&
                                            $terminal->longitude
                                        )
                                            {{ $terminal->latitude }},
                                            {{ $terminal->longitude }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="px-5 py-4">
                                        {{ $terminal->companies_count }}
                                    </td>

                                    <td class="px-5 py-4">
                                        {{ $terminal->distance_profiles_count }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1
                                                   text-xs font-semibold
                                                   {{
                                                       $terminal->is_active
                                                           ? 'bg-green-100 text-green-700'
                                                           : 'bg-gray-100 text-gray-600'
                                                   }}"
                                        >
                                            {{
                                                $terminal->is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'
                                            }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            @can('fleet-terminal.update')
                                                <a
                                                    href="{{ route(
                                                        'master-fleet.terminals.edit',
                                                        $terminal
                                                    ) }}"
                                                    class="rounded-lg border
                                                           px-3 py-1.5 text-xs
                                                           font-semibold text-blue-700"
                                                >
                                                    Edit
                                                </a>
                                            @endcan

                                            @can('fleet-terminal.disable')
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'master-fleet.terminals.toggle-active',
                                                        $terminal
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        class="rounded-lg border
                                                               px-3 py-1.5 text-xs
                                                               font-semibold
                                                               {{
                                                                   $terminal->is_active
                                                                       ? 'text-red-700'
                                                                       : 'text-green-700'
                                                               }}"
                                                    >
                                                        {{
                                                            $terminal->is_active
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
                                        colspan="6"
                                        class="px-5 py-10 text-center
                                               text-sm text-gray-500"
                                    >
                                        Belum ada data TLPG/Terminal.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t px-5 py-4">
                    {{ $terminals->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>