<x-app-layout>
    @include('master-fleet.partials.fleet-type-selector')
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Draft Grouping
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Kelola pembagian PC Final sebelum dipublikasikan
                    sebagai PC Set Utama.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('master-fleet.pc-set.index') }}"
                    class="inline-flex items-center justify-center rounded-lg
                           border border-gray-300 bg-white px-4 py-2
                           text-sm font-semibold text-gray-700 shadow-sm
                           hover:bg-gray-50"
                >
                    Lihat PC Set Utama
                </a>

                <a
                    href="{{ route('master-fleet.index') }}"
                    class="inline-flex items-center justify-center rounded-lg
                           border border-gray-300 bg-white px-4 py-2
                           text-sm font-semibold text-gray-700 shadow-sm
                           hover:bg-gray-50"
                >
                    Kembali ke Master Fleet
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $safeOperatorCount = max(
            1,
            min(
                50,
                (int) ($operatorCount ?? 12)
            )
        );

        $safeStatistics = array_merge(
            [
                'total' => 0,
                'unchanged' => 0,
                'moved' => 0,
                'new_vehicle' => 0,
                'manual' => 0,
                'distance_missing' => 0,
            ],
            $statistics ?? []
        );

        $safePcCounts = collect($pcCounts ?? []);

        $safeActiveTab =
            ($activeTab ?? 'draft') === 'ungrouped'
                ? 'ungrouped'
                : 'draft';

        $safeUngroupedCount =
            (int) ($ungroupedCount ?? 0);
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-[1600px] space-y-5 px-4 sm:px-6 lg:px-8">
            {{-- Pesan berhasil --}}
            @if(session('success'))
                <div
                    class="rounded-xl border border-green-200 bg-green-50
                           px-5 py-4 text-sm text-green-800"
                >
                    {{ session('success') }}
                </div>
            @endif

            {{-- Pesan gagal --}}
            @if(session('error'))
                <div
                    class="rounded-xl border border-red-200 bg-red-50
                           px-5 py-4 text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            {{-- Pesan validasi --}}
            @if($errors->any())
                <div
                    class="rounded-xl border border-red-200 bg-red-50
                           px-5 py-4 text-sm text-red-800"
                >
                    <p class="font-bold">
                        Data belum dapat diproses:
                    </p>

                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($draft === null)
                {{-- Belum ada draft --}}
                <section
                    class="rounded-xl border border-gray-200
                           bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-col justify-between gap-4 md:flex-row">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Buat Draft Grouping Baru
                            </h3>

                            @if($published)
                                <p class="mt-2 text-sm leading-6 text-gray-500">
                                    Draft akan disalin dari PC Set Utama
                                    <strong class="text-gray-800">
                                        {{ $published->name }}
                                    </strong>
                                    dengan
                                    <strong class="text-gray-800">
                                        {{ $published->assignments_count }}
                                    </strong>
                                    kendaraan.
                                </p>
                            @endif
                        </div>

                        @if($published)
                            <span
                                class="h-fit rounded-full bg-green-100
                                       px-3 py-1 text-xs font-bold text-green-700"
                            >
                                PC Set tersedia
                            </span>
                        @endif
                    </div>

                    @if($published)
                        <form
                            method="POST"
                            action="{{ route('master-fleet.grouping.create-draft') }}"
                            class="mt-6 grid gap-4 md:grid-cols-3"
                        >
                            @csrf

                            <div>
                                <label
                                    for="grouping-name"
                                    class="mb-2 block text-sm font-semibold text-gray-700"
                                >
                                    Nama Grouping
                                </label>

                                <input
                                    id="grouping-name"
                                    type="text"
                                    name="name"
                                    required
                                    maxlength="255"
                                    value="{{ old(
                                        'name',
                                        'Grouping ' . now()->translatedFormat('F Y')
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300 shadow-sm
                                           focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>

                            <div>
                                <label
                                    for="effective-date"
                                    class="mb-2 block text-sm font-semibold text-gray-700"
                                >
                                    Tanggal Berlaku
                                </label>

                                <input
                                    id="effective-date"
                                    type="date"
                                    name="effective_date"
                                    required
                                    value="{{ old(
                                        'effective_date',
                                        now()->toDateString()
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300 shadow-sm
                                           focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>

                            <div>
                                <label
                                    for="new-operator-count"
                                    class="mb-2 block text-sm font-semibold text-gray-700"
                                >
                                    Jumlah PC
                                </label>

                                <input
                                    id="new-operator-count"
                                    type="number"
                                    name="operator_count"
                                    min="1"
                                    max="50"
                                    required
                                    value="{{ old(
                                        'operator_count',
                                        $published->operator_count
                                            ?? config('master-fleet.operator_count', 12)
                                    ) }}"
                                    class="w-full rounded-lg border-gray-300 shadow-sm
                                           focus:border-blue-500 focus:ring-blue-500"
                                >

                                <p class="mt-1 text-xs text-gray-500">
                                    Jumlah PC masih dapat diubah selama
                                    grouping berstatus draft.
                                </p>
                            </div>

                            <div class="md:col-span-3">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center
                                           rounded-lg bg-blue-600 px-5 py-2.5
                                           text-sm font-bold text-white
                                           shadow-sm hover:bg-blue-700"
                                >
                                    Buat Draft dari PC Set Utama
                                </button>
                            </div>
                        </form>
                    @else
                        <div
                            class="mt-5 rounded-xl border border-yellow-200
                                   bg-yellow-50 p-5 text-sm text-yellow-800"
                        >
                            Belum ada PC Set Utama yang dapat dijadikan
                            sumber draft. Lakukan import atau publish
                            grouping pertama terlebih dahulu.
                        </div>
                    @endif
                </section>
            @else
                {{-- Informasi dan aksi draft --}}
                <section
                    class="rounded-xl border border-gray-200
                           bg-white p-5 shadow-sm"
                >
                    <div class="flex flex-col justify-between gap-5 xl:flex-row">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-bold text-gray-900">
                                    {{ $draft->name }}
                                </h3>

                                <span
                                    class="rounded-full bg-yellow-100 px-3 py-1
                                           text-xs font-bold text-yellow-800"
                                >
                                    Draft
                                </span>
                            </div>

                            <div
                                class="mt-2 flex flex-wrap gap-x-4 gap-y-1
                                       text-sm text-gray-500"
                            >
                                <span>
                                    Tanggal berlaku:
                                    <strong class="text-gray-700">
                                        {{
                                            optional($draft->effective_date)
                                                ->format('d-m-Y')
                                            ?: '-'
                                        }}
                                    </strong>
                                </span>

                                <span>
                                    Jumlah PC:
                                    <strong class="text-gray-700">
                                        {{ $safeOperatorCount }}
                                    </strong>
                                </span>

                                <span>
                                    Total kendaraan:
                                    <strong class="text-gray-700">
                                        {{ $safeStatistics['total'] }}
                                    </strong>
                                </span>
                            </div>

                            @if($draft->notes)
                                <p class="mt-2 max-w-3xl text-xs leading-5 text-gray-500">
                                    {{ $draft->notes }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-stretch gap-3">
                            {{-- Ubah jumlah PC --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'master-fleet.grouping.operator-count.update',
                                    $draft
                                ) }}"
                                class="rounded-xl border border-gray-200
                                       bg-gray-50 p-3"
                            >
                                @csrf
                                @method('PATCH')

                                <label
                                    for="operator-count"
                                    class="block text-xs font-semibold
                                           uppercase tracking-wide text-gray-600"
                                >
                                    Jumlah PC
                                </label>

                                <div class="mt-2 flex items-center gap-2">
                                    <button
                                        type="button"
                                        id="remove-one-pc"
                                        class="h-9 w-9 rounded-lg border
                                               border-gray-300 bg-white
                                               text-sm font-bold text-gray-700
                                               hover:bg-gray-100"
                                        title="Kurangi satu PC"
                                    >
                                        −
                                    </button>

                                    <input
                                        id="operator-count"
                                        type="number"
                                        name="operator_count"
                                        min="1"
                                        max="50"
                                        required
                                        value="{{ $safeOperatorCount }}"
                                        class="h-9 w-20 rounded-lg border-gray-300
                                               py-1 text-center text-sm font-bold"
                                    >

                                    <button
                                        type="button"
                                        id="add-one-pc"
                                        class="h-9 rounded-lg border
                                               border-blue-300 bg-blue-50 px-3
                                               text-xs font-bold text-blue-700
                                               hover:bg-blue-100"
                                    >
                                        +1 PC
                                    </button>

                                    <button
                                        type="submit"
                                        class="h-9 rounded-lg bg-gray-800
                                               px-3 text-xs font-bold text-white
                                               hover:bg-gray-900"
                                    >
                                        Simpan
                                    </button>
                                </div>

                                <p class="mt-1 text-[11px] text-gray-500">
                                    Generate ulang setelah jumlah PC berubah.
                                </p>
                            </form>

                            {{-- Hitung profil jarak tanpa mengubah pembagian PC --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'master-fleet.grouping.calculate-distances',
                                    $draft
                                ) }}"
                                class="rounded-xl border border-cyan-200
                                    bg-cyan-50 p-3"
                            >
                                @csrf

                                <p
                                    class="text-xs font-semibold uppercase
                                        tracking-wide text-cyan-800"
                                >
                                    Profil Jarak
                                </p>

                                <button
                                    type="submit"
                                    class="mt-2 w-full rounded-lg bg-cyan-600
                                        px-4 py-2 text-sm font-bold text-white
                                        hover:bg-cyan-700"
                                    onclick="
                                        return confirm(
                                            'Hitung profil jarak seluruh kendaraan? PC Lama dan PC Final tidak akan berubah.'
                                        );
                                    "
                                >
                                    Hitung Profil Jarak
                                </button>

                                <p class="mt-1 text-[11px] text-cyan-800">
                                    Tidak mengubah pembagian PC.
                                </p>
                            </form>

                            {{-- Generate --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'master-fleet.grouping.generate',
                                    $draft
                                ) }}"
                                class="rounded-xl border border-blue-200
                                       bg-blue-50 p-3"
                            >
                                @csrf

                                <input
                                    type="hidden"
                                    name="preserve_manual"
                                    value="0"
                                >

                                <label
                                    class="flex items-center gap-2
                                           text-xs font-semibold text-blue-900"
                                >
                                    <input
                                        type="checkbox"
                                        name="preserve_manual"
                                        value="1"
                                        checked
                                        class="rounded border-gray-300
                                               text-blue-600 focus:ring-blue-500"
                                    >

                                    Pertahankan edit manual
                                </label>

                                <button
                                    type="submit"
                                    class="mt-2 w-full rounded-lg bg-blue-600
                                           px-4 py-2 text-sm font-bold text-white
                                           hover:bg-blue-700"
                                    onclick="
                                        return confirm(
                                            'Generate ulang seluruh PC Final berdasarkan jarak, bobot, dan jumlah PC terbaru?'
                                        );
                                    "
                                >
                                    Generate PC Final
                                </button>
                            </form>

                            {{-- Reset --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'master-fleet.grouping.reset',
                                    $draft
                                ) }}"
                                class="flex"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="rounded-lg border border-orange-300
                                           bg-orange-50 px-4 py-3
                                           text-sm font-bold text-orange-800
                                           hover:bg-orange-100"
                                    onclick="
                                        return confirm(
                                            'Reset draft akan menghapus hasil generate, edit manual, dan kendaraan tambahan dari draft. Master kendaraan tidak dihapus. Lanjutkan?'
                                        );
                                    "
                                >
                                    Reset dari PC Set Utama
                                </button>
                            </form>

                            {{-- Publish --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'master-fleet.grouping.publish',
                                    $draft
                                ) }}"
                                class="flex"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="rounded-lg bg-green-600 px-5 py-3
                                           text-sm font-bold text-white
                                           hover:bg-green-700"
                                    onclick="
                                        return confirm(
                                            'Publikasikan PC Final sebagai PC Set Utama terbaru?'
                                        );
                                    "
                                >
                                    Konfirmasi dan Publish
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

                {{-- Statistik --}}
                <section
                    class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6"
                >
                    @php
                        $summaryCards = [
                            [
                                'label' => 'Total',
                                'value' => $safeStatistics['total'],
                                'class' => 'border-gray-200 bg-white text-gray-900',
                            ],
                            [
                                'label' => 'Tetap',
                                'value' => $safeStatistics['unchanged'],
                                'class' => 'border-green-200 bg-green-50 text-green-800',
                            ],
                            [
                                'label' => 'Pindah PC',
                                'value' => $safeStatistics['moved'],
                                'class' => 'border-blue-200 bg-blue-50 text-blue-800',
                            ],
                            [
                                'label' => 'Kendaraan Baru',
                                'value' => $safeStatistics['new_vehicle'],
                                'class' => 'border-purple-200 bg-purple-50 text-purple-800',
                            ],
                            [
                                'label' => 'Edit Manual',
                                'value' => $safeStatistics['manual'],
                                'class' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
                            ],
                            [
                                'label' => 'Jarak Belum Ada',
                                'value' => $safeStatistics['distance_missing'],
                                'class' => 'border-red-200 bg-red-50 text-red-800',
                            ],
                        ];
                    @endphp

                    @foreach($summaryCards as $card)
                        <div
                            class="rounded-xl border p-4 shadow-sm
                                   {{ $card['class'] }}"
                        >
                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wide opacity-75"
                            >
                                {{ $card['label'] }}
                            </p>

                            <p class="mt-1 text-2xl font-bold">
                                {{ number_format($card['value'], 0, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </section>

                @if($safeStatistics['distance_missing'] > 0)
                    <div
                        class="rounded-xl border border-yellow-200
                               bg-yellow-50 px-5 py-4 text-sm text-yellow-900"
                    >
                        <strong>
                            {{ $safeStatistics['distance_missing'] }}
                            kendaraan belum memiliki profil jarak.
                        </strong>

                        Data tetap dapat diedit manual, tetapi hasil generate
                        akan lebih akurat setelah koordinat perusahaan dan TLPG
                        dilengkapi.
                    </div>
                @endif

                {{-- Tab utama --}}
                <section
                    class="overflow-hidden rounded-xl border border-gray-200
                           bg-white shadow-sm"
                >
                    <div
                        class="flex overflow-x-auto border-b border-gray-200"
                    >
                        <a
                            href="{{ route(
                                'master-fleet.grouping.index',
                                ['tab' => 'draft']
                            ) }}"
                            class="whitespace-nowrap border-b-2 px-6 py-4
                                   text-sm font-bold
                                   {{
                                       $safeActiveTab === 'draft'
                                           ? 'border-blue-600 text-blue-700'
                                           : 'border-transparent text-gray-500 hover:text-gray-800'
                                   }}"
                        >
                            Data Draft

                            <span
                                class="ms-2 rounded-full bg-gray-100
                                       px-2 py-1 text-xs text-gray-700"
                            >
                                {{ $safeStatistics['total'] }}
                            </span>
                        </a>

                        <a
                            href="{{ route(
                                'master-fleet.grouping.index',
                                ['tab' => 'ungrouped']
                            ) }}"
                            class="whitespace-nowrap border-b-2 px-6 py-4
                                   text-sm font-bold
                                   {{
                                       $safeActiveTab === 'ungrouped'
                                           ? 'border-blue-600 text-blue-700'
                                           : 'border-transparent text-gray-500 hover:text-gray-800'
                                   }}"
                        >
                            Belum Tergrouping

                            <span
                                class="ms-2 rounded-full px-2 py-1 text-xs
                                       {{
                                           $safeUngroupedCount > 0
                                               ? 'bg-red-100 text-red-700'
                                               : 'bg-green-100 text-green-700'
                                       }}"
                            >
                                {{ $safeUngroupedCount }}
                            </span>
                        </a>
                    </div>

                    @if($safeActiveTab === 'draft')
                        <div class="space-y-5 p-5">
                            {{-- Jumlah per PC --}}
                            <div>
                                <div
                                    class="mb-3 flex flex-col justify-between
                                           gap-2 sm:flex-row sm:items-center"
                                >
                                    <div>
                                        <h3 class="font-bold text-gray-900">
                                            Kendaraan per PC Final
                                        </h3>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Klik PC untuk memfilter tabel kendaraan.
                                        </p>
                                    </div>

                                    <a
                                        href="{{ route(
                                            'master-fleet.grouping.index',
                                            [
                                                'tab' => 'draft',
                                                'q' => request('q'),
                                            ]
                                        ) }}"
                                        class="text-sm font-semibold text-blue-600
                                               hover:text-blue-800"
                                    >
                                        Tampilkan Semua PC
                                    </a>
                                </div>

                                <div class="flex gap-2 overflow-x-auto pb-2">
                                    @foreach(range(1, $safeOperatorCount) as $pc)
                                        <a
                                            href="{{ route(
                                                'master-fleet.grouping.index',
                                                array_filter(
                                                    [
                                                        'tab' => 'draft',
                                                        'pc' => $pc,
                                                        'q' => request('q'),
                                                    ],
                                                    fn ($value) =>
                                                        $value !== null
                                                        && $value !== ''
                                                )
                                            ) }}"
                                            class="min-w-20 rounded-xl border
                                                   px-3 py-2 text-center
                                                   transition hover:-translate-y-0.5
                                                   hover:shadow-sm
                                                   {{
                                                       request()->integer('pc') === $pc
                                                           ? 'border-blue-600 bg-blue-50'
                                                           : 'border-gray-200 bg-white'
                                                   }}"
                                        >
                                            <p
                                                class="text-xs font-semibold
                                                       {{
                                                           request()->integer('pc') === $pc
                                                               ? 'text-blue-700'
                                                               : 'text-gray-500'
                                                       }}"
                                            >
                                                PC {{ $pc }}
                                            </p>

                                            <p
                                                class="mt-1 text-xl font-bold
                                                       {{
                                                           request()->integer('pc') === $pc
                                                               ? 'text-blue-800'
                                                               : 'text-gray-900'
                                                       }}"
                                            >
                                                {{ $safePcCounts[$pc] ?? 0 }}
                                            </p>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Tambah nopol --}}
                            <details
                                class="rounded-xl border border-gray-200 bg-gray-50"
                                @if($errors->has('plate_number')
                                    || $errors->has('company_id')
                                    || $errors->has('terminal_id')
                                    || $errors->has('pc_final'))
                                    open
                                @endif
                            >
                                <summary
                                    class="flex cursor-pointer items-center
                                           justify-between px-5 py-4
                                           text-sm font-bold text-gray-800"
                                >
                                    <span>
                                        + Tambah Nopol Baru
                                    </span>

                                    <span class="text-xs font-normal text-gray-500">
                                        Buka form
                                    </span>
                                </summary>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'master-fleet.grouping.vehicles.store',
                                        $draft
                                    ) }}"
                                    class="grid gap-4 border-t border-gray-200
                                           bg-white p-5 md:grid-cols-2
                                           xl:grid-cols-5"
                                >
                                    @csrf

                                    <div>
                                        <label
                                            class="mb-2 block text-sm font-semibold
                                                   text-gray-700"
                                        >
                                            Nopol
                                        </label>

                                        <input
                                            type="text"
                                            name="plate_number"
                                            required
                                            maxlength="30"
                                            value="{{ old('plate_number') }}"
                                            placeholder="AE 1234 XX"
                                            class="w-full rounded-lg border-gray-300
                                                   uppercase shadow-sm"
                                        >
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
                                            class="w-full rounded-lg
                                                   border-gray-300 shadow-sm"
                                        >
                                            <option value="">
                                                Belum tersedia
                                            </option>

                                            @foreach($companies as $company)
                                                <option
                                                    value="{{ $company->id }}"
                                                    @selected(
                                                        (int) old('company_id')
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
                                            TLPG
                                        </label>

                                        <select
                                            name="terminal_id"
                                            required
                                            class="w-full rounded-lg
                                                   border-gray-300 shadow-sm"
                                        >
                                            <option value="">
                                                Pilih TLPG
                                            </option>

                                            @foreach($terminals as $terminal)
                                                <option
                                                    value="{{ $terminal->id }}"
                                                    @selected(
                                                        (int) old('terminal_id')
                                                        === $terminal->id
                                                    )
                                                >
                                                    {{ $terminal->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-sm font-semibold
                                                   text-gray-700"
                                        >
                                            PC Final
                                        </label>

                                        <select
                                            name="pc_final"
                                            required
                                            class="w-full rounded-lg
                                                   border-gray-300 shadow-sm"
                                        >
                                            @foreach(
                                                range(1, $safeOperatorCount)
                                                as $pc
                                            )
                                                <option
                                                    value="{{ $pc }}"
                                                    @selected(
                                                        (int) old('pc_final', 1)
                                                        === $pc
                                                    )
                                                >
                                                    PC {{ $pc }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex items-end">
                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-blue-600
                                                   px-4 py-2.5 text-sm font-bold
                                                   text-white hover:bg-blue-700"
                                        >
                                            Tambahkan ke Draft
                                        </button>
                                    </div>
                                </form>
                            </details>

                            {{-- Filter draft --}}
                            <form
                                id="draft-search-form"
                                method="GET"
                                action="{{ route('master-fleet.grouping.index') }}"
                                class="flex flex-col gap-3 md:flex-row"
                            >
                                <input
                                    type="hidden"
                                    name="tab"
                                    value="draft"
                                >

                                <div class="relative min-w-0 flex-1">
                                    <input
                                        id="draft-live-search"
                                        type="search"
                                        name="q"
                                        value="{{ request('q') }}"
                                        placeholder="Ketik nopol, perusahaan, atau TLPG..."
                                        autocomplete="off"
                                        data-live-search
                                        class="w-full rounded-lg border-gray-300
                                               pe-10 shadow-sm"
                                    >

                                    <div
                                        id="draft-search-loading"
                                        class="pointer-events-none absolute
                                               inset-y-0 right-3 hidden
                                               items-center text-xs text-gray-400"
                                    >
                                        Mencari...
                                    </div>
                                </div>

                                <select
                                    name="pc"
                                    data-auto-submit
                                    class="rounded-lg border-gray-300 shadow-sm"
                                >
                                    <option value="">
                                        Semua PC
                                    </option>

                                    @foreach(
                                        range(1, $safeOperatorCount)
                                        as $pc
                                    )
                                        <option
                                            value="{{ $pc }}"
                                            @selected(
                                                request()->integer('pc')
                                                === $pc
                                            )
                                        >
                                            PC {{ $pc }}
                                            ({{ $safePcCounts[$pc] ?? 0 }})
                                        </option>
                                    @endforeach
                                </select>

                                <button
                                    type="submit"
                                    class="rounded-lg bg-blue-600 px-5 py-2
                                           text-sm font-semibold text-white
                                           hover:bg-blue-700"
                                >
                                    Cari
                                </button>

                                <a
                                    href="{{ route(
                                        'master-fleet.grouping.index',
                                        ['tab' => 'draft']
                                    ) }}"
                                    class="rounded-lg border border-gray-300
                                           px-5 py-2 text-center text-sm
                                           font-semibold text-gray-700
                                           hover:bg-gray-50"
                                >
                                    Reset Filter
                                </a>
                            </form>

                            @php
                                $statusMap = [
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
                                        'label' => 'Disalin',
                                        'class' =>
                                            'bg-gray-100 text-gray-700',
                                    ],

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
                                        'label' => 'Data Final Saja',
                                        'class' =>
                                            'bg-gray-100 text-gray-700',
                                    ],

                                    'company_pending' => [
                                        'label' => 'Company Placeholder',
                                        'class' =>
                                            'bg-yellow-100 text-yellow-800',
                                    ],

                                    'company_unresolved' => [
                                        'label' => 'Company Belum Cocok',
                                        'class' =>
                                            'bg-red-100 text-red-700',
                                    ],
                                ];
                            @endphp

                            {{-- Tabel draft --}}
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table
                                    class="min-w-full divide-y divide-gray-200
                                           text-sm"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left
                                                       font-semibold text-gray-600"
                                            >
                                                Nopol
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left
                                                       font-semibold text-gray-600"
                                            >
                                                TLPG
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left
                                                       font-semibold text-gray-600"
                                            >
                                                Perusahaan
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left
                                                       font-semibold text-gray-600"
                                            >
                                                Profil Jarak
                                            </th>

                                            <th
                                                class="px-4 py-3 text-center
                                                       font-semibold text-gray-600"
                                            >
                                                PC Lama
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left
                                                       font-semibold text-gray-600"
                                            >
                                                PC Final
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left
                                                       font-semibold text-gray-600"
                                            >
                                                Status
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @forelse($assignments ?? [] as $assignment)
                                            @php
                                                $currentStatus = trim(
                                                    (string) (
                                                        $assignment
                                                            ->validation_status
                                                        ?? ''
                                                    )
                                                );

                                                $status =
                                                    $statusMap[$currentStatus]
                                                    ?? [
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
                                                           px-4 py-3 font-bold
                                                           text-gray-900"
                                                >
                                                    {{
                                                        $assignment
                                                            ->plate_number_snapshot
                                                    }}
                                                </td>

                                                <td class="px-4 py-3 text-gray-700">
                                                    {{
                                                        $assignment
                                                            ->terminal?->name
                                                        ??
                                                        $assignment
                                                            ->terminal_name_snapshot
                                                        ??
                                                        '-'
                                                    }}
                                                </td>

                                                <td class="px-4 py-3 text-gray-700">
                                                    {{
                                                        $assignment
                                                            ->company?->name
                                                        ??
                                                        $assignment
                                                            ->company_name_snapshot
                                                        ??
                                                        'Belum tersedia'
                                                    }}
                                                </td>

                                                <td
                                                    class="whitespace-nowrap
                                                           px-4 py-3"
                                                >
                                                    @if(
                                                        $assignment->distance_km
                                                        !== null
                                                    )
                                                        <div
                                                            class="font-semibold
                                                                   text-gray-800"
                                                        >
                                                            {{
                                                                number_format(
                                                                    (float)
                                                                    $assignment
                                                                        ->distance_km,
                                                                    2,
                                                                    ',',
                                                                    '.'
                                                                )
                                                            }}
                                                            km
                                                        </div>

                                                        <div
                                                            class="mt-0.5 text-xs
                                                                   text-gray-500"
                                                        >
                                                            {{
                                                                $assignment
                                                                    ->distance_category
                                                                ?: '-'
                                                            }}

                                                            · Bobot

                                                            {{
                                                                $assignment
                                                                    ->distance_weight
                                                                ?? '-'
                                                            }}
                                                        </div>
                                                    @else
                                                        <span
                                                            class="inline-flex
                                                                   rounded-full
                                                                   bg-red-50
                                                                   px-2 py-1
                                                                   text-xs
                                                                   font-semibold
                                                                   text-red-700"
                                                        >
                                                            Belum tersedia
                                                        </span>
                                                    @endif
                                                </td>

                                                <td
                                                    class="px-4 py-3 text-center"
                                                >
                                                    @if($assignment->pc_initial)
                                                        <span
                                                            class="inline-flex
                                                                   rounded-full
                                                                   bg-gray-100
                                                                   px-3 py-1
                                                                   text-xs
                                                                   font-bold
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

                                                <td class="px-4 py-3">
                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'master-fleet.grouping.assignments.update',
                                                            [
                                                                $draft,
                                                                $assignment,
                                                            ]
                                                        ) }}"
                                                        class="flex min-w-44
                                                               items-center gap-2"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <select
                                                            name="pc_final"
                                                            class="rounded-lg
                                                                   border-gray-300
                                                                   py-1.5 text-sm
                                                                   shadow-sm"
                                                        >
                                                            @foreach(
                                                                range(
                                                                    1,
                                                                    $safeOperatorCount
                                                                )
                                                                as $pc
                                                            )
                                                                <option
                                                                    value="{{ $pc }}"
                                                                    @selected(
                                                                        (int)
                                                                        $assignment
                                                                            ->pc_final
                                                                        ===
                                                                        $pc
                                                                    )
                                                                >
                                                                    PC {{ $pc }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg
                                                                   bg-blue-600
                                                                   px-3 py-1.5
                                                                   text-xs
                                                                   font-bold
                                                                   text-white
                                                                   hover:bg-blue-700"
                                                        >
                                                            Simpan
                                                        </button>
                                                    </form>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <span
                                                        class="inline-flex
                                                               rounded-full
                                                               px-3 py-1
                                                               text-xs
                                                               font-semibold
                                                               {{ $status['class'] }}"
                                                    >
                                                        {{ $status['label'] }}
                                                    </span>

                                                    @if(
                                                        $assignment
                                                            ->validation_notes
                                                    )
                                                        <p
                                                            class="mt-1 max-w-xs
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

                                                    @if(
                                                        $assignment
                                                            ->manual_adjustment_note
                                                    )
                                                        <p
                                                            class="mt-1 max-w-xs
                                                                   text-xs
                                                                   leading-5
                                                                   text-yellow-700"
                                                        >
                                                            Catatan:
                                                            {{
                                                                $assignment
                                                                    ->manual_adjustment_note
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
                                                    Data draft tidak ditemukan.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(
                                $assignments
                                &&
                                $assignments->hasPages()
                            )
                                <div>
                                    {{ $assignments->links() }}
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- Tab belum tergrouping --}}
                        <div class="space-y-5 p-5">
                            <div
                                class="rounded-xl border border-blue-200
                                       bg-blue-50 p-4 text-sm
                                       leading-6 text-blue-900"
                            >
                                Daftar ini berisi kendaraan aktif pada
                                Master Kendaraan yang belum masuk ke Draft
                                Grouping. Pilih TLPG dan PC Final untuk
                                memasukkannya.
                            </div>

                            <form
                                id="ungrouped-search-form"
                                method="GET"
                                action="{{ route('master-fleet.grouping.index') }}"
                                class="flex flex-col gap-3 md:flex-row"
                            >
                                <input
                                    type="hidden"
                                    name="tab"
                                    value="ungrouped"
                                >

                                <div class="relative min-w-0 flex-1">
                                    <input
                                        id="ungrouped-live-search"
                                        type="search"
                                        name="q"
                                        value="{{ request('q') }}"
                                        placeholder="Ketik nopol atau nama perusahaan..."
                                        autocomplete="off"
                                        data-live-search
                                        class="w-full rounded-lg
                                               border-gray-300 shadow-sm"
                                    >

                                    <div
                                        id="ungrouped-search-loading"
                                        class="pointer-events-none absolute
                                               inset-y-0 right-3 hidden
                                               items-center text-xs
                                               text-gray-400"
                                    >
                                        Mencari...
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    class="rounded-lg bg-blue-600 px-5 py-2
                                           text-sm font-semibold text-white
                                           hover:bg-blue-700"
                                >
                                    Cari
                                </button>

                                <a
                                    href="{{ route(
                                        'master-fleet.grouping.index',
                                        ['tab' => 'ungrouped']
                                    ) }}"
                                    class="rounded-lg border border-gray-300
                                           px-5 py-2 text-center text-sm
                                           font-semibold text-gray-700
                                           hover:bg-gray-50"
                                >
                                    Reset
                                </a>
                            </form>

                            @if($safeUngroupedCount === 0)
                                <div
                                    class="rounded-xl border border-green-200
                                           bg-green-50 p-8 text-center
                                           text-green-800"
                                >
                                    <p class="font-bold">
                                        Semua kendaraan aktif sudah masuk
                                        Draft Grouping.
                                    </p>

                                    <p class="mt-1 text-sm">
                                        Tidak ada kendaraan yang perlu
                                        dimasukkan lagi.
                                    </p>
                                </div>
                            @elseif(!$ungroupedVehicles)
                                <div
                                    class="rounded-xl border border-gray-200
                                           bg-gray-50 p-8 text-center
                                           text-gray-500"
                                >
                                    Data kendaraan belum tergrouping
                                    belum berhasil dimuat.
                                </div>
                            @else
                                @php
                                    $suggestedPc =
                                        collect($safePcCounts)
                                            ->sort()
                                            ->keys()
                                            ->first()
                                        ?? 1;
                                @endphp

                                <div
                                    class="overflow-x-auto rounded-xl
                                           border border-gray-200"
                                >
                                    <table
                                        class="min-w-full divide-y
                                               divide-gray-200 text-sm"
                                    >
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-4 py-3 text-left
                                                           font-semibold
                                                           text-gray-600"
                                                >
                                                    Nopol
                                                </th>

                                                <th
                                                    class="px-4 py-3 text-left
                                                           font-semibold
                                                           text-gray-600"
                                                >
                                                    Perusahaan
                                                </th>

                                                <th
                                                    class="px-4 py-3 text-left
                                                           font-semibold
                                                           text-gray-600"
                                                >
                                                    Pilih TLPG
                                                </th>

                                                <th
                                                    class="px-4 py-3 text-left
                                                           font-semibold
                                                           text-gray-600"
                                                >
                                                    PC Final
                                                </th>

                                                <th
                                                    class="px-4 py-3 text-left
                                                           font-semibold
                                                           text-gray-600"
                                                >
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody
                                            class="divide-y divide-gray-100
                                                   bg-white"
                                        >
                                            @forelse(
                                                $ungroupedVehicles
                                                as $vehicle
                                            )
                                                <tr class="hover:bg-gray-50">
                                                    <td
                                                        class="whitespace-nowrap
                                                               px-4 py-3
                                                               font-bold
                                                               text-gray-900"
                                                    >
                                                        {{
                                                            $vehicle
                                                                ->plate_number
                                                        }}
                                                    </td>

                                                    <td
                                                        class="px-4 py-3
                                                               text-gray-700"
                                                    >
                                                        {{
                                                            $vehicle
                                                                ->company?->name
                                                            ??
                                                            'Belum tersedia'
                                                        }}
                                                    </td>

                                                    <td
                                                        colspan="3"
                                                        class="px-4 py-3"
                                                    >
                                                        <form
                                                            method="POST"
                                                            action="{{ route(
                                                                'master-fleet.grouping.vehicles.store',
                                                                $draft
                                                            ) }}"
                                                            class="grid gap-2
                                                                   md:grid-cols-[minmax(180px,1fr)_120px_auto]"
                                                        >
                                                            @csrf

                                                            <input
                                                                type="hidden"
                                                                name="plate_number"
                                                                value="{{
                                                                    $vehicle
                                                                        ->plate_number
                                                                }}"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="company_id"
                                                                value="{{
                                                                    $vehicle
                                                                        ->company_id
                                                                }}"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="note"
                                                                value="Ditambahkan dari daftar kendaraan belum tergrouping."
                                                            >

                                                            <select
                                                                name="terminal_id"
                                                                required
                                                                class="rounded-lg
                                                                       border-gray-300
                                                                       text-sm
                                                                       shadow-sm"
                                                            >
                                                                <option value="">
                                                                    Pilih TLPG
                                                                </option>

                                                                @foreach(
                                                                    $terminals
                                                                    as $terminal
                                                                )
                                                                    <option
                                                                        value="{{
                                                                            $terminal
                                                                                ->id
                                                                        }}"
                                                                        @selected(
                                                                            (int) (
                                                                                $vehicle
                                                                                    ->company
                                                                                    ?->default_terminal_id
                                                                                ?? 0
                                                                            )
                                                                            ===
                                                                            $terminal
                                                                                ->id
                                                                        )
                                                                    >
                                                                        {{
                                                                            $terminal
                                                                                ->name
                                                                        }}
                                                                    </option>
                                                                @endforeach
                                                            </select>

                                                            <select
                                                                name="pc_final"
                                                                required
                                                                class="rounded-lg
                                                                       border-gray-300
                                                                       text-sm
                                                                       shadow-sm"
                                                            >
                                                                @foreach(
                                                                    range(
                                                                        1,
                                                                        $safeOperatorCount
                                                                    )
                                                                    as $pc
                                                                )
                                                                    <option
                                                                        value="{{
                                                                            $pc
                                                                        }}"
                                                                        @selected(
                                                                            (int)
                                                                            $suggestedPc
                                                                            ===
                                                                            $pc
                                                                        )
                                                                    >
                                                                        PC
                                                                        {{ $pc }}
                                                                        ({{
                                                                            $safePcCounts[
                                                                                $pc
                                                                            ]
                                                                            ?? 0
                                                                        }})
                                                                    </option>
                                                                @endforeach
                                                            </select>

                                                            <button
                                                                type="submit"
                                                                class="rounded-lg
                                                                       bg-blue-600
                                                                       px-4 py-2
                                                                       text-xs
                                                                       font-bold
                                                                       text-white
                                                                       hover:bg-blue-700"
                                                            >
                                                                Masukkan ke Draft
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td
                                                        colspan="5"
                                                        class="px-5 py-12
                                                               text-center
                                                               text-gray-500"
                                                    >
                                                        Kendaraan tidak ditemukan.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if($ungroupedVehicles->hasPages())
                                    <div>
                                        {{ $ungroupedVehicles->links() }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /*
             * Pencarian otomatis.
             * Form dikirim setelah pengguna berhenti mengetik.
             */
            document
                .querySelectorAll('[data-live-search]')
                .forEach(function (input) {
                    let searchTimer = null;

                    input.addEventListener('input', function () {
                        window.clearTimeout(searchTimer);

                        searchTimer = window.setTimeout(function () {
                            if (!input.form) {
                                return;
                            }

                            try {
                                window.sessionStorage.setItem(
                                    'masterFleetSearchFocus',
                                    input.id
                                );

                                window.sessionStorage.setItem(
                                    'masterFleetSearchCaret',
                                    String(input.selectionStart || 0)
                                );
                            } catch (error) {
                                // Penyimpanan fokus tidak wajib.
                            }

                            const loadingId =
                                input.id === 'draft-live-search'
                                    ? 'draft-search-loading'
                                    : 'ungrouped-search-loading';

                            const loadingElement =
                                document.getElementById(loadingId);

                            if (loadingElement) {
                                loadingElement.classList.remove('hidden');
                                loadingElement.classList.add('flex');
                            }

                            input.form.requestSubmit();
                        }, 450);
                    });
                });

            /*
             * Dropdown PC langsung menerapkan filter.
             */
            document
                .querySelectorAll('[data-auto-submit]')
                .forEach(function (select) {
                    select.addEventListener('change', function () {
                        if (select.form) {
                            select.form.requestSubmit();
                        }
                    });
                });

            /*
             * Tambah dan kurangi jumlah PC.
             */
            const operatorCountInput =
                document.getElementById('operator-count');

            const addPcButton =
                document.getElementById('add-one-pc');

            const removePcButton =
                document.getElementById('remove-one-pc');

            if (operatorCountInput && addPcButton) {
                addPcButton.addEventListener('click', function () {
                    const currentValue =
                        Number.parseInt(
                            operatorCountInput.value || '1',
                            10
                        );

                    operatorCountInput.value =
                        Math.min(
                            50,
                            Math.max(
                                1,
                                currentValue + 1
                            )
                        );

                    operatorCountInput.focus();
                });
            }

            if (operatorCountInput && removePcButton) {
                removePcButton.addEventListener('click', function () {
                    const currentValue =
                        Number.parseInt(
                            operatorCountInput.value || '1',
                            10
                        );

                    operatorCountInput.value =
                        Math.max(
                            1,
                            currentValue - 1
                        );

                    operatorCountInput.focus();
                });
            }

            /*
             * Mengembalikan fokus setelah live search memuat halaman.
             */
            try {
                const focusId =
                    window.sessionStorage.getItem(
                        'masterFleetSearchFocus'
                    );

                const caretValue =
                    Number.parseInt(
                        window.sessionStorage.getItem(
                            'masterFleetSearchCaret'
                        ) || '0',
                        10
                    );

                if (focusId) {
                    const focusElement =
                        document.getElementById(focusId);

                    if (focusElement) {
                        focusElement.focus();

                        if (
                            typeof focusElement.setSelectionRange
                            === 'function'
                        ) {
                            const caretPosition =
                                Math.min(
                                    caretValue,
                                    focusElement.value.length
                                );

                            focusElement.setSelectionRange(
                                caretPosition,
                                caretPosition
                            );
                        }
                    }

                    window.sessionStorage.removeItem(
                        'masterFleetSearchFocus'
                    );

                    window.sessionStorage.removeItem(
                        'masterFleetSearchCaret'
                    );
                }
            } catch (error) {
                // Pemulihan fokus tidak wajib.
            }
        });
    </script>
</x-app-layout>