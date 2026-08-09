<x-app-layout>
    @include('master-fleet.partials.fleet-type-selector')
    <x-slot name="header">
        <div
            class="flex flex-col justify-between gap-3
                   md:flex-row md:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    PC Set Utama
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Daftar pembagian final kendaraan yang sedang digunakan.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{
                        route(
                            'master-fleet.google-workspace.index'
                        )
                    }}"
                    class="inline-flex items-center justify-center
                           rounded-lg bg-emerald-600 px-4 py-2
                           text-sm font-semibold text-white
                           shadow-sm hover:bg-emerald-700"
                >
                    Google Workspace
                </a>

                <a
                    href="{{ route('master-fleet.grouping.index') }}"
                    class="inline-flex items-center justify-center
                           rounded-lg bg-blue-600 px-4 py-2
                           text-sm font-semibold text-white
                           shadow-sm hover:bg-blue-700"
                >
                    Draft Grouping
                </a>

                <a
                    href="{{ route('master-fleet.index') }}"
                    class="inline-flex items-center justify-center
                           rounded-lg border border-gray-300
                           bg-white px-4 py-2 text-sm font-semibold
                           text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Kembali ke Master Fleet
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-[1600px]
                   space-y-6 px-4 sm:px-6 lg:px-8"
        >
            {{-- Pesan berhasil --}}
            @if(session('success'))
                <div
                    class="rounded-xl border border-green-200
                           bg-green-50 px-5 py-4
                           text-sm text-green-800"
                >
                    {{ session('success') }}
                </div>
            @endif

            {{-- Pesan error --}}
            @if(session('error'))
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4
                           text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            {{-- Validation error --}}
            @if($errors->any())
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4
                           text-sm text-red-800"
                >
                    <div class="font-bold">
                        Data filter tidak valid.
                    </div>

                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($period === null)
                {{-- Belum ada grouping published --}}
                <div
                    class="rounded-xl border border-yellow-200
                           bg-yellow-50 p-6 text-yellow-900"
                >
                    <h3 class="font-bold">
                        Belum ada PC Set Utama
                    </h3>

                    <p class="mt-2 text-sm">
                        Belum ditemukan periode grouping dengan status
                        published. Lakukan import Master Fleet atau
                        publikasikan Draft Grouping terlebih dahulu.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            href="{{ route('master-fleet.import.index') }}"
                            class="inline-flex rounded-lg
                                   bg-blue-600 px-4 py-2
                                   text-sm font-semibold text-white
                                   hover:bg-blue-700"
                        >
                            Buka Import Master Fleet
                        </a>

                        <a
                            href="{{ route('master-fleet.grouping.index') }}"
                            class="inline-flex rounded-lg
                                   border border-yellow-300
                                   bg-white px-4 py-2
                                   text-sm font-semibold text-yellow-800
                                   hover:bg-yellow-100"
                        >
                            Buka Draft Grouping
                        </a>
                    </div>
                </div>
            @else
                @php
                    /*
                    |--------------------------------------------------------------------------
                    | Statistik aman untuk data migrasi lama dan grouping baru
                    |--------------------------------------------------------------------------
                    */

                    $totalCount =
                        (int) (
                            $statistics['total']
                            ?? 0
                        );

                    $unchangedCount =
                        (int) (
                            $statistics['unchanged']
                            ??
                            $statistics['matched']
                            ??
                            0
                        );

                    $movedCount =
                        (int) (
                            $statistics['moved']
                            ??
                            $statistics['pc_changed']
                            ??
                            0
                        );

                    $newVehicleCount =
                        (int) (
                            $statistics['new_vehicle']
                            ??
                            $statistics['final_only']
                            ??
                            0
                        );

                    $manualCount =
                        (int) (
                            $statistics['manual']
                            ?? 0
                        );

                    $companyPendingCount =
                        (int) (
                            $statistics['company_pending']
                            ?? 0
                        );

                    $distanceMissingCount =
                        (int) (
                            $statistics['distance_missing']
                            ?? 0
                        );

                    $needCheckCount =
                        $companyPendingCount
                        +
                        $distanceMissingCount;
                @endphp

                {{-- Informasi periode --}}
                <section
                    class="rounded-xl border border-gray-200
                           bg-white p-6 shadow-sm"
                >
                    <div
                        class="flex flex-col justify-between gap-4
                               lg:flex-row lg:items-center"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    {{ $period->name }}
                                </h3>

                                <span
                                    class="rounded-full bg-green-100
                                           px-3 py-1 text-xs font-bold
                                           text-green-700"
                                >
                                    PC Set Aktif
                                </span>
                            </div>

                            <p class="mt-2 text-sm text-gray-500">
                                Tanggal berlaku:

                                <strong class="text-gray-700">
                                    {{
                                        $period->effective_date
                                            ? $period->effective_date
                                                ->format('d-m-Y')
                                            : '-'
                                    }}
                                </strong>

                                <span class="mx-2">·</span>

                                Dipublikasikan:

                                <strong class="text-gray-700">
                                    {{
                                        $period->published_at
                                            ? $period->published_at
                                                ->format('d-m-Y H:i')
                                            : '-'
                                    }}
                                </strong>
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                Sumber:
                                {{ $period->source_file_name ?: '-' }}
                            </p>
                        </div>

                        <div class="text-left lg:text-right">
                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wide text-gray-500"
                            >
                                Kendaraan Aktif
                            </p>

                            <p class="mt-1 text-4xl font-bold text-gray-900">
                                {{
                                    number_format(
                                        $totalCount,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Statistik --}}
                <section
                    class="grid gap-4
                           sm:grid-cols-2 lg:grid-cols-3
                           xl:grid-cols-6"
                >
                    <div
                        class="rounded-xl border border-gray-200
                               bg-white p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wide text-gray-500"
                        >
                            Total Kendaraan
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $totalCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-green-200
                               bg-green-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wide text-green-700"
                        >
                            Tetap
                        </p>

                        <p class="mt-2 text-3xl font-bold text-green-800">
                            {{ $unchangedCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-blue-200
                               bg-blue-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wide text-blue-700"
                        >
                            Pindah PC
                        </p>

                        <p class="mt-2 text-3xl font-bold text-blue-800">
                            {{ $movedCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-purple-200
                               bg-purple-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wide text-purple-700"
                        >
                            Kendaraan Baru
                        </p>

                        <p class="mt-2 text-3xl font-bold text-purple-800">
                            {{ $newVehicleCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-yellow-200
                               bg-yellow-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wide text-yellow-700"
                        >
                            Edit Manual
                        </p>

                        <p class="mt-2 text-3xl font-bold text-yellow-800">
                            {{ $manualCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-red-200
                               bg-red-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wide text-red-700"
                        >
                            Perlu Dicek
                        </p>

                        <p class="mt-2 text-3xl font-bold text-red-800">
                            {{ $needCheckCount }}
                        </p>
                    </div>
                </section>

                {{-- Kartu PC --}}
                <section
                    class="rounded-xl border border-gray-200
                           bg-white p-6 shadow-sm"
                >
                    <div
                        class="mb-4 flex flex-col justify-between gap-3
                               md:flex-row md:items-center"
                    >
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Jumlah Kendaraan per PC Final
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Klik kartu untuk menampilkan kendaraan
                                pada PC tersebut.
                            </p>
                        </div>

                        <a
                            href="{{
                                route(
                                    'master-fleet.pc-set.index',
                                    request()->except([
                                        'pc',
                                        'page',
                                    ])
                                )
                            }}"
                            class="rounded-lg border px-4 py-2
                                   text-sm font-semibold
                                   {{
                                       empty($filters['pc'])
                                           ? 'border-blue-600 bg-blue-600 text-white'
                                           : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                   }}"
                        >
                            Semua PC
                        </a>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3
                               sm:grid-cols-3 lg:grid-cols-6
                               xl:grid-cols-12"
                    >
                        @foreach(range(1, $operatorCount) as $pc)
                            <a
                                href="{{
                                    route(
                                        'master-fleet.pc-set.index',
                                        array_merge(
                                            request()->except([
                                                'page',
                                                'pc',
                                            ]),
                                            [
                                                'pc' => $pc,
                                            ]
                                        )
                                    )
                                }}"
                                class="rounded-xl border p-4
                                       text-center transition
                                       hover:-translate-y-0.5
                                       hover:shadow-sm
                                       {{
                                           (int) (
                                               $filters['pc']
                                               ?? 0
                                           ) === $pc
                                               ? 'border-blue-600 bg-blue-50'
                                               : 'border-gray-200 bg-white'
                                       }}"
                            >
                                <p
                                    class="text-xs font-semibold uppercase
                                           {{
                                               (int) (
                                                   $filters['pc']
                                                   ?? 0
                                               ) === $pc
                                                   ? 'text-blue-700'
                                                   : 'text-gray-500'
                                           }}"
                                >
                                    PC {{ $pc }}
                                </p>

                                <p
                                    class="mt-1 text-2xl font-bold
                                           {{
                                               (int) (
                                                   $filters['pc']
                                                   ?? 0
                                               ) === $pc
                                                   ? 'text-blue-800'
                                                   : 'text-gray-900'
                                           }}"
                                >
                                    {{ $pcCounts[$pc] ?? 0 }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Filter --}}
                <section
                    class="rounded-xl border border-gray-200
                           bg-white p-5 shadow-sm"
                >
                    <form
                        method="GET"
                        action="{{ route('master-fleet.pc-set.index') }}"
                    >
                        <div
                            class="grid gap-4
                                   md:grid-cols-2 xl:grid-cols-6"
                        >
                            <div class="xl:col-span-2">
                                <label
                                    for="search"
                                    class="mb-2 block text-sm
                                           font-semibold text-gray-700"
                                >
                                    Cari
                                </label>

                                <input
                                    id="search"
                                    type="text"
                                    name="q"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Nopol, TLPG, atau perusahaan"
                                    class="w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                            </div>

                            <div>
                                <label
                                    for="pc"
                                    class="mb-2 block text-sm
                                           font-semibold text-gray-700"
                                >
                                    PC Final
                                </label>

                                <select
                                    id="pc"
                                    name="pc"
                                    class="w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                                    <option value="">
                                        Semua PC
                                    </option>

                                    @foreach(
                                        range(1, $operatorCount)
                                        as $pc
                                    )
                                        <option
                                            value="{{ $pc }}"
                                            @selected(
                                                (int) (
                                                    $filters['pc']
                                                    ?? 0
                                                ) === $pc
                                            )
                                        >
                                            PC {{ $pc }}
                                            ({{ $pcCounts[$pc] ?? 0 }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    for="terminal"
                                    class="mb-2 block text-sm
                                           font-semibold text-gray-700"
                                >
                                    TLPG
                                </label>

                                <select
                                    id="terminal"
                                    name="terminal_id"
                                    class="w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                                    <option value="">
                                        Semua TLPG
                                    </option>

                                    @foreach($terminals as $terminal)
                                        <option
                                            value="{{ $terminal->id }}"
                                            @selected(
                                                (int) (
                                                    $filters['terminal_id']
                                                    ?? 0
                                                ) === $terminal->id
                                            )
                                        >
                                            {{ $terminal->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    for="company"
                                    class="mb-2 block text-sm
                                           font-semibold text-gray-700"
                                >
                                    Perusahaan
                                </label>

                                <select
                                    id="company"
                                    name="company_id"
                                    class="w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                                    <option value="">
                                        Semua Perusahaan
                                    </option>

                                    @foreach($companies as $company)
                                        <option
                                            value="{{ $company->id }}"
                                            @selected(
                                                (int) (
                                                    $filters['company_id']
                                                    ?? 0
                                                ) === $company->id
                                            )
                                        >
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    for="status"
                                    class="mb-2 block text-sm
                                           font-semibold text-gray-700"
                                >
                                    Status
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                    class="w-full rounded-lg
                                           border-gray-300 shadow-sm
                                           focus:border-blue-500
                                           focus:ring-blue-500"
                                >
                                    <option value="">
                                        Semua Status
                                    </option>

                                    <optgroup label="Grouping Baru">
                                        <option
                                            value="unchanged"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'unchanged'
                                            )
                                        >
                                            Tetap
                                        </option>

                                        <option
                                            value="moved"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'moved'
                                            )
                                        >
                                            Pindah PC
                                        </option>

                                        <option
                                            value="new_vehicle"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'new_vehicle'
                                            )
                                        >
                                            Kendaraan Baru
                                        </option>

                                        <option
                                            value="manual"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'manual'
                                            )
                                        >
                                            Diubah Manual
                                        </option>

                                        <option
                                            value="distance_missing"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'distance_missing'
                                            )
                                        >
                                            Jarak Belum Lengkap
                                        </option>
                                    </optgroup>

                                    <optgroup label="Data Migrasi">
                                        <option
                                            value="matched"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'matched'
                                            )
                                        >
                                            Data Cocok
                                        </option>

                                        <option
                                            value="pc_changed"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'pc_changed'
                                            )
                                        >
                                            Perubahan PC
                                        </option>

                                        <option
                                            value="final_only"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'final_only'
                                            )
                                        >
                                            Tanpa Data Rotasi
                                        </option>

                                        <option
                                            value="company_pending"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'company_pending'
                                            )
                                        >
                                            Perusahaan Placeholder
                                        </option>

                                        <option
                                            value="company_unresolved"
                                            @selected(
                                                ($filters['status'] ?? '')
                                                === 'company_unresolved'
                                            )
                                        >
                                            Perusahaan Belum Cocok
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex flex-col justify-between
                                   gap-3 sm:flex-row sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <label
                                    for="per-page"
                                    class="text-sm text-gray-600"
                                >
                                    Tampilkan
                                </label>

                                <select
                                    id="per-page"
                                    name="per_page"
                                    class="rounded-lg border-gray-300
                                           text-sm shadow-sm"
                                >
                                    @foreach([25, 50, 100] as $size)
                                        <option
                                            value="{{ $size }}"
                                            @selected(
                                                (int) (
                                                    $filters['per_page']
                                                    ?? 25
                                                ) === $size
                                            )
                                        >
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>

                                <span class="text-sm text-gray-600">
                                    data
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <a
                                    href="{{
                                        route(
                                            'master-fleet.pc-set.index'
                                        )
                                    }}"
                                    class="rounded-lg border
                                           border-gray-300 bg-white
                                           px-4 py-2 text-sm
                                           font-semibold text-gray-700
                                           hover:bg-gray-50"
                                >
                                    Reset
                                </a>

                                <button
                                    type="submit"
                                    class="rounded-lg bg-blue-600
                                           px-5 py-2 text-sm
                                           font-semibold text-white
                                           hover:bg-blue-700"
                                >
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                {{-- Daftar kendaraan --}}
                <section
                    class="overflow-hidden rounded-xl
                           border border-gray-200
                           bg-white shadow-sm"
                >
                    <div
                        class="flex flex-col justify-between gap-3
                               border-b border-gray-200
                               px-5 py-4 sm:flex-row
                               sm:items-center"
                    >
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Daftar Kendaraan
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Menampilkan
                                {{ $assignments->firstItem() ?? 0 }}
                                sampai
                                {{ $assignments->lastItem() ?? 0 }}
                                dari
                                {{ $assignments->total() }}
                                kendaraan.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y
                                   divide-gray-200 text-sm"
                        >
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left
                                               font-semibold text-gray-600"
                                    >
                                        No
                                    </th>

                                    <th
                                        class="px-5 py-3 text-left
                                               font-semibold text-gray-600"
                                    >
                                        Nopol
                                    </th>

                                    <th
                                        class="px-5 py-3 text-left
                                               font-semibold text-gray-600"
                                    >
                                        TLPG
                                    </th>

                                    <th
                                        class="px-5 py-3 text-left
                                               font-semibold text-gray-600"
                                    >
                                        Perusahaan
                                    </th>

                                    <th
                                        class="px-5 py-3 text-center
                                               font-semibold text-gray-600"
                                    >
                                        PC Lama
                                    </th>

                                    <th
                                        class="px-5 py-3 text-center
                                               font-semibold text-gray-600"
                                    >
                                        PC Final
                                    </th>

                                    <th
                                        class="px-5 py-3 text-left
                                               font-semibold text-gray-600"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-100 bg-white"
                            >
                                @forelse($assignments as $assignment)
                                    @php
                                        /*
                                        |--------------------------------------------------------------------------
                                        | Seluruh status berada di dalam satu array
                                        |--------------------------------------------------------------------------
                                        */

                                        $statusMap = [
                                            /*
                                             * Status grouping baru.
                                             */
                                            'unchanged' => [
                                                'label' => 'Tetap',
                                                'class' =>
                                                    'bg-green-100 text-green-700',
                                            ],

                                            'moved' => [
                                                'label' => 'Pindah PC',
                                                'class' =>
                                                    'bg-blue-100 text-blue-700',
                                            ],

                                            'new_vehicle' => [
                                                'label' => 'Kendaraan Baru',
                                                'class' =>
                                                    'bg-purple-100 text-purple-700',
                                            ],

                                            'manual' => [
                                                'label' => 'Diubah Manual',
                                                'class' =>
                                                    'bg-yellow-100 text-yellow-800',
                                            ],

                                            'distance_missing' => [
                                                'label' => 'Jarak Belum Lengkap',
                                                'class' =>
                                                    'bg-red-100 text-red-700',
                                            ],

                                            'copied' => [
                                                'label' => 'Disalin dari PC Set Lama',
                                                'class' =>
                                                    'bg-gray-100 text-gray-700',
                                            ],

                                            /*
                                             * Status hasil migrasi awal.
                                             */
                                            'matched' => [
                                                'label' => 'Data Cocok',
                                                'class' =>
                                                    'bg-green-100 text-green-700',
                                            ],

                                            'pc_changed' => [
                                                'label' => 'Perubahan PC',
                                                'class' =>
                                                    'bg-blue-100 text-blue-700',
                                            ],

                                            'final_only' => [
                                                'label' => 'Tanpa Data Rotasi',
                                                'class' =>
                                                    'bg-gray-100 text-gray-700',
                                            ],

                                            'company_pending' => [
                                                'label' =>
                                                    'Perusahaan Placeholder',

                                                'class' =>
                                                    'bg-yellow-100 text-yellow-800',
                                            ],

                                            'company_unresolved' => [
                                                'label' =>
                                                    'Perusahaan Belum Cocok',

                                                'class' =>
                                                    'bg-red-100 text-red-700',
                                            ],
                                        ];

                                        $currentStatus =
                                            trim(
                                                (string) (
                                                    $assignment
                                                        ->validation_status
                                                    ?? ''
                                                )
                                            );

                                        $status =
                                            $statusMap[$currentStatus]
                                            ??
                                            [
                                                'label' =>
                                                    $currentStatus !== ''
                                                        ? ucwords(
                                                            str_replace(
                                                                '_',
                                                                ' ',
                                                                $currentStatus
                                                            )
                                                        )
                                                        : 'Belum Ada Status',

                                                'class' =>
                                                    'bg-gray-100 text-gray-700',
                                            ];
                                    @endphp

                                    <tr class="hover:bg-gray-50">
                                        <td
                                            class="whitespace-nowrap
                                                   px-5 py-3 text-gray-500"
                                        >
                                            {{
                                                ($assignments->firstItem() ?? 1)
                                                +
                                                $loop->index
                                            }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap
                                                   px-5 py-3 font-bold
                                                   text-gray-900"
                                        >
                                            {{
                                                $assignment
                                                    ->plate_number_snapshot
                                            }}
                                        </td>

                                        <td class="px-5 py-3 text-gray-700">
                                            {{
                                                $assignment->terminal?->name
                                                ??
                                                $assignment
                                                    ->terminal_name_snapshot
                                                ??
                                                '-'
                                            }}
                                        </td>

                                        <td class="px-5 py-3 text-gray-700">
                                            {{
                                                $assignment->company?->name
                                                ??
                                                $assignment
                                                    ->company_name_snapshot
                                                ??
                                                'Belum tersedia'
                                            }}
                                        </td>

                                        <td
                                            class="px-5 py-3 text-center
                                                   text-gray-700"
                                        >
                                            @if($assignment->pc_initial)
                                                <span
                                                    class="inline-flex
                                                           rounded-full
                                                           bg-gray-100
                                                           px-3 py-1
                                                           text-xs font-bold
                                                           text-gray-700"
                                                >
                                                    PC
                                                    {{
                                                        $assignment
                                                            ->pc_initial
                                                    }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">
                                                    -
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-5 py-3 text-center">
                                            @if($assignment->pc_final)
                                                <span
                                                    class="inline-flex
                                                           rounded-full
                                                           bg-blue-600
                                                           px-3 py-1
                                                           text-xs font-bold
                                                           text-white"
                                                >
                                                    PC
                                                    {{
                                                        $assignment
                                                            ->pc_final
                                                    }}
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex
                                                           rounded-full
                                                           bg-red-100
                                                           px-3 py-1
                                                           text-xs font-bold
                                                           text-red-700"
                                                >
                                                    Belum Ada
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-5 py-3">
                                            <span
                                                class="inline-flex
                                                       rounded-full
                                                       px-3 py-1
                                                       text-xs font-semibold
                                                       {{ $status['class'] }}"
                                            >
                                                {{ $status['label'] }}
                                            </span>

                                            @if($assignment->validation_notes)
                                                <p
                                                    class="mt-1 max-w-md
                                                           text-xs
                                                           leading-5
                                                           text-gray-500"
                                                >
                                                    {{
                                                        $assignment
                                                            ->validation_notes
                                                    }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="px-5 py-12
                                                   text-center
                                                   text-gray-500"
                                        >
                                            Tidak ada kendaraan yang sesuai
                                            dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($assignments->hasPages())
                        <div
                            class="border-t border-gray-200
                                   px-5 py-4"
                        >
                            {{ $assignments->links() }}
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>