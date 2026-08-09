<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">
                    Jenis Armada Master Fleet
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Master Fleet tetap satu database, tetapi MT LPG dan MT PERTASHOP dipisahkan saat import, grouping, dan PC Set.
                </p>
            </div>

            <a
                href="{{ route('master-fleet.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Kembali ke Master Fleet
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Total Armada
                </div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ number_format($statistics['total']) }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                    MT LPG
                </div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800">
                    {{ number_format($statistics['lpg']) }}
                </div>
            </div>

            <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-violet-700">
                    MT PERTASHOP
                </div>
                <div class="mt-2 text-2xl font-semibold text-violet-800">
                    {{ number_format($statistics['pertashop']) }}
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 p-4">
                <form
                    method="GET"
                    action="{{ route('master-fleet.fleet-type.index') }}"
                    class="grid gap-3 md:grid-cols-[1fr_220px_auto_auto]"
                >
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari nopol atau perusahaan..."
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <select
                        name="type"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            Semua jenis armada
                        </option>

                        @foreach ($options as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($filter === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Cari
                    </button>

                    <a
                        href="{{ route('master-fleet.fleet-type.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Nopol
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Perusahaan
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                Jenis Armada
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">
                                    {{ $vehicle->plate_number }}
                                </td>

                                <td class="px-4 py-3 text-slate-600">
                                    {{ $vehicle->company?->name ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($vehicle->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="min-w-72 px-4 py-3">
                                    <form
                                        method="POST"
                                        action="{{ route('master-fleet.fleet-type.update', $vehicle) }}"
                                        class="flex items-center gap-2"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <select
                                            name="fleet_type"
                                            class="min-w-44 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            @foreach ($options as $value => $label)
                                                <option
                                                    value="{{ $value }}"
                                                    @selected($vehicle->fleet_type === $value)
                                                >
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button
                                            type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Simpan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-slate-500"
                                >
                                    Data kendaraan tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 p-4">
                {{ $vehicles->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
