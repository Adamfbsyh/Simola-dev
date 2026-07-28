<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Master Fleet
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Pusat data TLPG, SPBE, jarak,
                kendaraan, dan grouping operasional.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-5 rounded-lg border border-green-200
                            bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-lg border border-red-200
                            bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">
                        TLPG / Terminal Aktif
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $activeTerminalCount }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        Total {{ $terminalCount }} data
                    </p>
                </div>

                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">
                        SPBE / Perusahaan Aktif
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $activeCompanyCount }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        Total {{ $companyCount }} data
                    </p>
                </div>

                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">
                        Profil Jarak
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $distanceProfileCount }}
                    </p>
                </div>

                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">
                        Kendaraan Aktif
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $activeVehicleCount }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        Total {{ $vehicleCount }} data
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <a
                    href="{{ route(
                        'master-fleet.terminals.index'
                    ) }}"
                    class="rounded-xl border bg-white p-6 shadow-sm
                           transition hover:-translate-y-1 hover:shadow-md"
                >
                    <h3 class="text-lg font-bold text-gray-900">
                        Master TLPG / Terminal
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Kelola nama TLPG/depo beserta
                        latitude dan longitude.
                    </p>
                </a>

                <a
                    href="{{ route(
                        'master-fleet.companies.index'
                    ) }}"
                    class="rounded-xl border bg-white p-6 shadow-sm
                           transition hover:-translate-y-1 hover:shadow-md"
                >
                    <h3 class="text-lg font-bold text-gray-900">
                        Master SPBE / Perusahaan
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Kelola perusahaan, koordinat,
                        pasangan TLPG, jarak, kategori,
                        dan bobot.
                    </p>
                </a>
            </div>

            <div class="mt-6 rounded-xl border border-blue-200
                        bg-blue-50 p-5 text-sm text-blue-900">
                Modul ini masih berada pada lingkungan development.
                Data production dan laporan lama belum diubah.
            </div>
        </div>
    </div>
</x-app-layout>