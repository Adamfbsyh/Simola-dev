@php
    $summary =
        $analysis['summary'] ?? [];

    $comparison =
        $analysis['comparison'] ?? [];

    $pcMismatch =
        $comparison['pc_mismatch'] ?? [];

    $terminalMismatch =
        $comparison['terminal_mismatch'] ?? [];

    $companyMismatch =
        $comparison['company_mismatch'] ?? [];

    $missingInFinal =
        $comparison['missing_in_final'] ?? [];

    $missingInRotation =
        $comparison['missing_in_rotation'] ?? [];

    $rotationDuplicates =
        $summary['rotation_duplicates'] ?? 0;

    $finalDuplicates =
        $summary['final_duplicates'] ?? 0;

    $issueCount =
        count($pcMismatch)
        +
        count($terminalMismatch)
        +
        count($companyMismatch)
        +
        count($missingInFinal)
        +
        count($missingInRotation)
        +
        $rotationDuplicates
        +
        $finalDuplicates;
@endphp

<section
    class="w-full overflow-hidden rounded-2xl
           border border-gray-200 bg-white shadow-sm"
>
    {{-- Header --}}
    <div
        class="flex flex-col justify-between gap-4
               border-b border-gray-200 px-6 py-5
               lg:flex-row lg:items-center"
    >
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-gray-900">
                Hasil Analisis Migrasi
            </h2>

            <p class="mt-1 break-all text-sm text-gray-500">
                Batch:
                <span class="font-mono font-semibold text-gray-700">
                    {{ $batch->uuid }}
                </span>
            </p>

            <p class="mt-1 break-words text-xs text-gray-500">
                File:
                {{ $batch->original_name }}
            </p>
        </div>

        <span
            class="self-start rounded-full px-4 py-2
                   text-sm font-bold lg:self-auto
                   {{
                       ($summary['ready_for_import'] ?? false)
                           ? 'bg-green-100 text-green-700'
                           : 'bg-yellow-100 text-yellow-800'
                   }}"
        >
            {{
                ($summary['ready_for_import'] ?? false)
                    ? 'Siap Dilanjutkan'
                    : 'Perlu Pemeriksaan'
            }}
        </span>
    </div>

    <div class="space-y-7 p-6">
        {{-- Data sumber --}}
        <div>
            <h3
                class="mb-3 text-sm font-bold uppercase
                       tracking-wide text-gray-600"
            >
                Data Sumber
            </h3>

            <div
                class="grid gap-4
                       sm:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    class="rounded-xl border border-gray-200
                           bg-gray-50 p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        Master TLPG
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['terminal_rows'] ?? 0 }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Invalid:
                        <span
                            class="font-bold
                                   {{
                                       ($summary['terminal_invalid'] ?? 0) > 0
                                           ? 'text-red-600'
                                           : 'text-green-600'
                                   }}"
                        >
                            {{ $summary['terminal_invalid'] ?? 0 }}
                        </span>
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-gray-50 p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        Master Perusahaan
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['company_rows'] ?? 0 }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Invalid:
                        <span
                            class="font-bold
                                   {{
                                       ($summary['company_invalid'] ?? 0) > 0
                                           ? 'text-red-600'
                                           : 'text-green-600'
                                   }}"
                        >
                            {{ $summary['company_invalid'] ?? 0 }}
                        </span>
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-gray-50 p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        Setting Rotasi
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{
                            $summary[
                                'unique_rotation_vehicles'
                            ]
                            ?? 0
                        }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Duplikat:
                        <span
                            class="font-bold
                                   {{
                                       $rotationDuplicates > 0
                                           ? 'text-red-600'
                                           : 'text-green-600'
                                   }}"
                        >
                            {{ $rotationDuplicates }}
                        </span>
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-gray-50 p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        PC Set Utama
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{
                            $summary[
                                'unique_final_vehicles'
                            ]
                            ?? 0
                        }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Duplikat:
                        <span
                            class="font-bold
                                   {{
                                       $finalDuplicates > 0
                                           ? 'text-red-600'
                                           : 'text-green-600'
                                   }}"
                        >
                            {{ $finalDuplicates }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Hasil perbandingan --}}
        <div>
            <div
                class="mb-3 flex flex-col justify-between gap-2
                       sm:flex-row sm:items-center"
            >
                <h3
                    class="text-sm font-bold uppercase
                           tracking-wide text-gray-600"
                >
                    Hasil Perbandingan
                </h3>

                <span class="text-xs text-gray-500">
                    Total masalah terdeteksi:
                    <strong class="text-gray-900">
                        {{ $issueCount }}
                    </strong>
                </span>
            </div>

            <div
                class="grid gap-4
                       sm:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    class="rounded-xl border border-green-200
                           bg-green-50 p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-green-700"
                    >
                        Data Cocok
                    </p>

                    <p class="mt-2 text-3xl font-bold text-green-800">
                        {{ $summary['matched'] ?? 0 }}
                    </p>

                    <p class="mt-1 text-xs text-green-700">
                        Rotasi dan hasil final sesuai.
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
                        PERUBAHAN PC FINAL
                    </p>

                    <p class="mt-2 text-3xl font-bold text-red-800">
                        {{ $summary['pc_mismatch'] ?? 0 }}
                    </p>

                    <p class="mt-1 text-xs text-red-700">
                        PC target rotasi berbeda dari hasil final.
                        PC SET UTAMA tetap menjadi hasil resmi.
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
                        Tidak Ada di PC Set Utama
                    </p>

                    <p class="mt-2 text-3xl font-bold text-yellow-800">
                        {{ $summary['missing_in_final'] ?? 0 }}
                    </p>

                    <p class="mt-1 text-xs text-yellow-700">
                        Ada di rotasi tetapi tidak ditemukan di hasil final.
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-white p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        Tidak Ada di Rotasi
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['missing_in_rotation'] ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-white p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        Terminal Berbeda
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['terminal_mismatch'] ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-white p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase
                               tracking-wide text-gray-500"
                    >
                        Company Placeholder
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['placeholder_companies'] ?? 0 }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Contoh: Data Company Belum Ada.
                    </p>
                </div>
            </div>
        </div>

        {{-- Detail PC berbeda --}}
        @if(count($pcMismatch) > 0)
            <details
                class="overflow-hidden rounded-xl
                       border border-red-200"
            >
                <summary
                    class="flex cursor-pointer list-none
                           items-center justify-between gap-4
                           bg-red-50 px-5 py-4"
                >
                    <div>
                        <h3 class="font-bold text-red-800">
                            Perbedaan PC Target dan PC Final
                        </h3>

                        <p class="mt-1 text-xs text-red-700">
                            Klik untuk membuka detail kendaraan.
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-red-100
                               px-3 py-1 text-xs font-bold
                               text-red-700"
                    >
                        {{ count($pcMismatch) }} data
                    </span>
                </summary>

                <div class="max-h-[32rem] overflow-auto">
                    <table
                        class="min-w-full divide-y
                               divide-gray-200 text-sm"
                    >
                        <thead
                            class="sticky top-0 z-10 bg-gray-50"
                        >
                            <tr>
                                <th
                                    class="px-5 py-3 text-left
                                           font-semibold text-gray-600"
                                >
                                    Nopol
                                </th>

                                <th
                                    class="px-5 py-3 text-center
                                           font-semibold text-gray-600"
                                >
                                    PC Awal
                                </th>

                                <th
                                    class="px-5 py-3 text-center
                                           font-semibold text-gray-600"
                                >
                                    PC Target Rotasi
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
                                    Baris Sumber
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-100 bg-white"
                        >
                            @foreach($pcMismatch as $item)
                                @php
                                    $pcInitial =
                                        $item['pc_initial']
                                        ?? null;

                                    $pcTarget =
                                        $item['pc_target']
                                        ?? null;

                                    $pcFinal =
                                        $item['pc_final']
                                        ?? null;
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td
                                        class="whitespace-nowrap
                                               px-5 py-3 font-bold
                                               text-gray-900"
                                    >
                                        {{ $item['plate_number'] }}
                                    </td>

                                    <td class="px-5 py-3 text-center">
                                        {{
                                            $pcInitial !== null
                                                ? 'PC ' . $pcInitial
                                                : '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-3 text-center">
                                        <span
                                            class="rounded-full
                                                   bg-yellow-100
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   text-yellow-800"
                                        >
                                            {{
                                                $pcTarget !== null
                                                    ? 'PC ' . $pcTarget
                                                    : '-'
                                            }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3 text-center">
                                        <span
                                            class="rounded-full
                                                   bg-red-100
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   text-red-700"
                                        >
                                            {{
                                                $pcFinal !== null
                                                    ? 'PC ' . $pcFinal
                                                    : '-'
                                            }}
                                        </span>
                                    </td>

                                    <td
                                        class="whitespace-nowrap
                                               px-5 py-3 text-xs
                                               text-gray-500"
                                    >
                                        Rotasi:
                                        {{
                                            $item['rotation_row']
                                            ?? '-'
                                        }}

                                        <span class="mx-1">·</span>

                                        Final:
                                        {{
                                            $item['final_row']
                                            ?? '-'
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif

        {{-- Tidak ada di final --}}
        @if(count($missingInFinal) > 0)
            <details
                class="overflow-hidden rounded-xl
                       border border-yellow-200"
            >
                <summary
                    class="flex cursor-pointer list-none
                           items-center justify-between gap-4
                           bg-yellow-50 px-5 py-4"
                >
                    <div>
                        <h3 class="font-bold text-yellow-800">
                            Nopol Tidak Ditemukan di PC Set Utama
                        </h3>

                        <p class="mt-1 text-xs text-yellow-700">
                            Kendaraan terdapat pada Setting Rotasi,
                            tetapi tidak ada pada hasil final.
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-yellow-100
                               px-3 py-1 text-xs font-bold
                               text-yellow-800"
                    >
                        {{ count($missingInFinal) }} data
                    </span>
                </summary>

                <div class="max-h-80 overflow-auto">
                    <table
                        class="min-w-full divide-y
                               divide-gray-200 text-sm"
                    >
                        <thead
                            class="sticky top-0 bg-gray-50"
                        >
                            <tr>
                                <th class="px-5 py-3 text-left">
                                    Nopol
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Terminal
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Perusahaan
                                </th>

                                <th class="px-5 py-3 text-center">
                                    PC Target
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y bg-white">
                            @foreach($missingInFinal as $item)
                                <tr>
                                    <td
                                        class="px-5 py-3
                                               font-semibold"
                                    >
                                        {{ $item['plate_number'] }}
                                    </td>

                                    <td class="px-5 py-3">
                                        {{
                                            $item['terminal']
                                            ?: '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-3">
                                        {{
                                            $item['company']
                                            ?: '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-3 text-center">
                                        {{
                                            ($item['pc_target'] ?? null)
                                                !== null
                                                    ? 'PC '
                                                        . $item[
                                                            'pc_target'
                                                        ]
                                                    : '-'
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif

        {{-- Tidak ada di rotasi --}}
        @if(count($missingInRotation) > 0)
            <details
                class="overflow-hidden rounded-xl
                       border border-gray-200"
            >
                <summary
                    class="flex cursor-pointer list-none
                           items-center justify-between gap-4
                           bg-gray-50 px-5 py-4"
                >
                    <div>
                        <h3 class="font-bold text-gray-800">
                            Nopol Tidak Ditemukan di Setting Rotasi
                        </h3>

                        <p class="mt-1 text-xs text-gray-600">
                            Kendaraan ada di PC Set Utama,
                            tetapi tidak ditemukan pada Setting Rotasi.
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-gray-200
                               px-3 py-1 text-xs font-bold
                               text-gray-700"
                    >
                        {{ count($missingInRotation) }} data
                    </span>
                </summary>

                <div class="max-h-80 overflow-auto">
                    <table
                        class="min-w-full divide-y
                               divide-gray-200 text-sm"
                    >
                        <thead
                            class="sticky top-0 bg-gray-50"
                        >
                            <tr>
                                <th class="px-5 py-3 text-left">
                                    Nopol
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Terminal
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Perusahaan
                                </th>

                                <th class="px-5 py-3 text-center">
                                    PC Final
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y bg-white">
                            @foreach($missingInRotation as $item)
                                <tr>
                                    <td
                                        class="px-5 py-3
                                               font-semibold"
                                    >
                                        {{ $item['plate_number'] }}
                                    </td>

                                    <td class="px-5 py-3">
                                        {{
                                            $item['terminal']
                                            ?: '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-3">
                                        {{
                                            $item['company']
                                            ?: '-'
                                        }}
                                    </td>

                                    <td class="px-5 py-3 text-center">
                                        {{
                                            ($item['pc_final'] ?? null)
                                                !== null
                                                    ? 'PC '
                                                        . $item[
                                                            'pc_final'
                                                        ]
                                                    : '-'
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif

        {{-- Keamanan --}}
       @php
    $officialVehicleCount =
        $summary['official_vehicle_count']
        ??
        $summary['unique_final_vehicles']
        ??
        0;

    $rotationOnlyIgnored =
        $summary['rotation_only_ignored']
        ??
        $summary['missing_in_final']
        ??
        0;

    $finalWithoutRotation =
        $summary['final_without_rotation']
        ??
        $summary['missing_in_rotation']
        ??
        0;

    $readyForImport =
        (bool) (
            $summary['ready_for_import']
            ?? false
        );
@endphp

<div
    class="rounded-xl border border-blue-200
           bg-blue-50 px-5 py-4 text-sm text-blue-900"
>
    <div class="font-bold">
        Database belum diubah
    </div>

    <p class="mt-1 leading-6">
        Sumber resmi kendaraan adalah PC SET UTAMA,
        yaitu {{ $officialVehicleCount }} nopol.
    </p>

    <ul class="mt-3 list-disc space-y-1 ps-5 text-xs">
        <li>
            {{ $rotationOnlyIgnored }} nopol yang hanya ada di
            Setting Rotasi tidak akan dibuat sebagai kendaraan aktif.
        </li>

        <li>
            {{ $finalWithoutRotation }} nopol yang hanya ada di
            PC Set Utama tetap diimpor dengan PC awal dan target kosong.
        </li>

        <li>
            Perubahan PC target ke PC final tetap disimpan
            sebagai histori grouping.
        </li>
    </ul>
</div>

<form
    method="POST"
    action="{{ route(
        'master-fleet.import.confirm',
        $batch
    ) }}"
    class="rounded-xl border border-green-200
           bg-green-50 p-5"
>
    @csrf

    <input
        type="hidden"
        name="confirmation_count"
        value="{{ $officialVehicleCount }}"
    >

    <div
        class="grid gap-5
               lg:grid-cols-2"
    >
        <div>
            <label
                for="grouping-name"
                class="mb-2 block text-sm font-semibold
                       text-gray-700"
            >
                Nama Periode Grouping
            </label>

            <input
                id="grouping-name"
                type="text"
                name="grouping_name"
                required
                maxlength="255"
                value="{{ old(
                    'grouping_name',
                    'Migrasi Awal Master Fleet'
                ) }}"
                class="w-full rounded-lg border-gray-300
                       shadow-sm focus:border-blue-500
                       focus:ring-blue-500"
            >
        </div>

        <div>
            <label
                for="effective-date"
                class="mb-2 block text-sm font-semibold
                       text-gray-700"
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
                class="w-full rounded-lg border-gray-300
                       shadow-sm focus:border-blue-500
                       focus:ring-blue-500"
            >
        </div>
    </div>

    <div
        class="mt-5 rounded-lg border border-green-200
               bg-white p-4"
    >
        <input
            type="hidden"
            name="sync_snapshot"
            value="0"
        >

        <label
            class="flex cursor-pointer items-start gap-3"
        >
            <input
                type="checkbox"
                name="sync_snapshot"
                value="1"
                checked
                class="mt-1 rounded border-gray-300
                       text-blue-600 focus:ring-blue-500"
            >

            <span>
                <span
                    class="block text-sm font-semibold
                           text-gray-800"
                >
                    Sinkronkan sebagai snapshot resmi
                </span>

                <span
                    class="mt-1 block text-xs
                           leading-5 text-gray-500"
                >
                    Kendaraan aktif yang tidak ada dalam
                    {{ $officialVehicleCount }} nopol PC SET UTAMA
                    akan dinonaktifkan, bukan dihapus.
                    Gunakan pilihan ini agar jumlah kendaraan aktif
                    tepat {{ $officialVehicleCount }}.
                </span>
            </span>
        </label>
    </div>

    <div
        class="mt-5 flex flex-col justify-between
               gap-4 border-t border-green-200 pt-5
               sm:flex-row sm:items-center"
    >
        <div>
            <p class="text-sm font-bold text-green-900">
                Data yang akan diimpor
            </p>

            <p class="mt-1 text-xs text-green-800">
                {{ $summary['terminal_rows'] ?? 0 }} TLPG,
                {{ $summary['company_rows'] ?? 0 }} perusahaan,
                dan {{ $officialVehicleCount }} kendaraan resmi.
            </p>
        </div>

        <button
            type="submit"
            @disabled(!$readyForImport)
            class="inline-flex items-center justify-center
                   rounded-lg px-5 py-3 text-sm
                   font-bold text-white shadow-sm
                   {{
                       $readyForImport
                           ? 'bg-green-600 hover:bg-green-700'
                           : 'cursor-not-allowed bg-gray-400'
                   }}"
            onclick="
                return confirm(
                    'Import {{ $officialVehicleCount }} kendaraan resmi dari PC SET UTAMA?'
                );
            "
        >
            Konfirmasi Import
            {{ $officialVehicleCount }}
            Nopol
        </button>
    </div>
</form>
    </div>
</section>