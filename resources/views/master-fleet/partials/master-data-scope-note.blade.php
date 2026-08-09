@php
    $masterFleetType =
        \App\Support\MasterFleet\FleetType::current(
            request()
        );

    $masterFleetTypeLabel =
        \App\Support\MasterFleet\FleetType::label(
            $masterFleetType
        );

    $masterCompanyTypeLabel =
        $masterFleetType
        ===
        \App\Support\MasterFleet\FleetType::PERTASHOP
            ? 'SPBU / Perusahaan'
            : 'SPBE / Perusahaan';
@endphp

<div class="mx-auto w-full max-w-7xl px-4 pb-1 sm:px-6 lg:px-8">
    <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
                    Master Armada Terpilih
                </span>

                <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-indigo-700">
                    {{ $masterFleetTypeLabel }}
                </span>
            </div>

            <p class="mt-2 max-w-2xl text-xs leading-5 text-indigo-800">
                Master Kendaraan, Terminal/TLPG, {{ $masterCompanyTypeLabel }}, Profil Jarak,
                Import Spreadsheet, Draft Grouping, dan PC Set Utama hanya menampilkan atau memproses
                {{ $masterFleetTypeLabel }}.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                Pemisahan Data Armada
            </div>

            <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-500">
                MT LPG dan MT PERTASHOP mempunyai Master Terminal/TLPG, perusahaan sumber
                (SPBE atau SPBU), dan Profil Jarak masing-masing. Data antar armada tidak dicampur.
            </p>
        </div>
    </div>
</div>
