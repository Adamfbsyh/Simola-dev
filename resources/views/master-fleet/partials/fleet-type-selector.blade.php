@php
    $fleetTypeCurrent =
        \App\Support\MasterFleet\FleetType::current(
            request()
        );

    $fleetTypeOptions =
        \App\Support\MasterFleet\FleetType::options();
@endphp

<div class="mx-auto w-full max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Jenis Armada Aktif
                    </span>

                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        {{ \App\Support\MasterFleet\FleetType::label($fleetTypeCurrent) }}
                    </span>
                </div>

                <p class="mt-1 max-w-3xl text-xs text-slate-500">
                    Kendaraan, Terminal/TLPG, SPBE/SPBU, Profil Jarak, Import, Draft Grouping, dan PC Set
                    mengikuti jenis armada aktif. Data MT LPG dan MT PERTASHOP tidak dicampur.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <form
                    method="GET"
                    action="{{ url()->current() }}"
                    class="flex flex-col gap-2 sm:flex-row sm:items-end"
                >
                    @foreach (request()->query() as $queryKey => $queryValue)
                        @if ($queryKey !== 'fleet_type' && is_scalar($queryValue))
                            <input
                                type="hidden"
                                name="{{ $queryKey }}"
                                value="{{ $queryValue }}"
                            >
                        @endif
                    @endforeach

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-slate-500">
                            Pilih armada
                        </span>

                        <select
                            name="fleet_type"
                            class="h-9 min-w-44 rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach ($fleetTypeOptions as $fleetTypeValue => $fleetTypeLabel)
                                <option
                                    value="{{ $fleetTypeValue }}"
                                    @selected($fleetTypeCurrent === $fleetTypeValue)
                                >
                                    {{ $fleetTypeLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button
                        type="submit"
                        class="h-9 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Gunakan
                    </button>
                </form>

                @if (\Illuminate\Support\Facades\Route::has('master-fleet.audit.index'))
                    <a
                        href="{{ route('master-fleet.audit.index') }}"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                    >
                        Riwayat Perubahan
                    </a>
                @endif

                @if (\Illuminate\Support\Facades\Route::has('master-fleet.fleet-type.index'))
                    <a
                        href="{{ route('master-fleet.fleet-type.index') }}"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Kelola Jenis Armada
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
