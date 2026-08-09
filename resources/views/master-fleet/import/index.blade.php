<x-app-layout>
    @include('master-fleet.partials.fleet-type-selector')

    @include('master-fleet.compare.entry')
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Import Spreadsheet Master Fleet
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Periksa struktur workbook sebelum data dimasukkan
                ke database SIMOLA.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
            {{-- Validasi --}}
            @if($errors->any())
                <div
                    class="mb-5 rounded-xl border border-red-200
                           bg-red-50 px-5 py-4 text-sm text-red-800"
                >
                    <div class="font-bold">
                        Proses belum dapat dilanjutkan.
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

            @if(session('error'))
                <div
                    class="mb-5 rounded-xl border border-red-200
                           bg-red-50 px-5 py-4 text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div
                    class="mb-5 rounded-xl border border-green-200
                           bg-green-50 px-5 py-4 text-sm text-green-800"
                >
                    {{ session('success') }}
                </div>
            @endif

            {{-- Informasi keamanan --}}
            <div
                class="mb-6 rounded-xl border border-blue-200
                       bg-blue-50 px-5 py-4 text-sm text-blue-900"
            >
                <div class="font-bold">
                    Tahap pemeriksaan saja
                </div>

                <p class="mt-1 leading-6">
                    Upload pada halaman ini belum menambah,
                    memperbarui, atau menghapus data database.
                    Sistem hanya membaca struktur workbook,
                    formula, nilai hasil, dan contoh data.
                </p>
            </div>

            {{-- Form upload --}}
            <form
                method="POST"
                action="{{ route('master-fleet.import.preview') }}"
                enctype="multipart/form-data"
                class="rounded-xl border border-gray-200
                       bg-white p-6 shadow-sm"
            >
                @csrf

                <div
                    class="grid items-end gap-4
                           md:grid-cols-[minmax(0,1fr)_auto]"
                >
                    <div>
                        <label
                            for="spreadsheet"
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700"
                        >
                            File Spreadsheet
                        </label>

                        <input
                            id="spreadsheet"
                            type="file"
                            name="spreadsheet"
                            required
                            accept=".xlsx,.xls"
                            class="block w-full rounded-lg border
                                   border-gray-300 bg-white px-3 py-2
                                   text-sm text-gray-700 shadow-sm
                                   file:me-4 file:rounded-lg
                                   file:border-0 file:bg-blue-50
                                   file:px-4 file:py-2
                                   file:text-sm file:font-semibold
                                   file:text-blue-700
                                   hover:file:bg-blue-100
                                   focus:border-blue-500
                                   focus:ring-blue-500"
                        >

                        <p class="mt-2 text-xs text-gray-500">
                            Format XLSX atau XLS. Ukuran maksimal 20 MB.
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center
                               rounded-lg bg-blue-600 px-5 py-2.5
                               text-sm font-semibold text-white
                               shadow-sm transition hover:bg-blue-700
                               focus:outline-none focus:ring-2
                               focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Baca dan Preview
                    </button>
                </div>
            </form>

            @if(!empty($preview))
                <div class="mt-8 space-y-6">
                    {{-- Ringkasan file --}}
                    <div
                        class="grid gap-4
                               sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <div
                            class="min-w-0 rounded-xl border
                                   border-gray-200 bg-white
                                   p-5 shadow-sm"
                        >
                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wide text-gray-500"
                            >
                                Nama File
                            </p>

                            <p
                                class="mt-2 break-words text-sm
                                       font-bold text-gray-900"
                            >
                                {{ $preview['file_name'] }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-gray-200
                                   bg-white p-5 shadow-sm"
                        >
                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wide text-gray-500"
                            >
                                Ukuran
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ $preview['file_size_label'] }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-gray-200
                                   bg-white p-5 shadow-sm"
                        >
                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wide text-gray-500"
                            >
                                Format Reader
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ $preview['reader_type'] }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-gray-200
                                   bg-white p-5 shadow-sm"
                        >
                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wide text-gray-500"
                            >
                                Jumlah Sheet
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ $preview['sheet_count'] }}
                            </p>
                        </div>
                    </div>

                    {{--
                        PENTING:
                        Hasil analisis berada di luar grid empat kartu.
                    --}}
                    @if(
                        !empty($analysis)
                        &&
                        !empty($batch)
                    )
                        @include(
                            'master-fleet.import._analysis',
                            [
                                'analysis' => $analysis,
                                'batch' => $batch,
                            ]
                        )

                        @php
                            $p1SheetFound = (bool) data_get(
                                $analysis,
                                'summary.p1_sheet_found',
                                false
                            );

                            $officialVehicleCount = (int) data_get(
                                $analysis,
                                'summary.official_vehicle_count',
                                0
                            );

                            $p1VehicleCount = (int) data_get(
                                $analysis,
                                'summary.p1_vehicle_count',
                                0
                            );

                            $p2VehicleCount = (int) data_get(
                                $analysis,
                                'summary.p2_vehicle_count',
                                max(
                                    0,
                                    $officialVehicleCount
                                    -
                                    $p1VehicleCount
                                )
                            );

                            $p1DuplicatesResolved = (int) data_get(
                                $analysis,
                                'summary.p1_duplicates_resolved',
                                0
                            );

                            $p1ConflictsResolved = (int) data_get(
                                $analysis,
                                'summary.p1_conflicts_resolved',
                                0
                            );
                        @endphp

                        <div
                            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                class="rounded-xl border border-gray-200
                                       bg-white p-5 shadow-sm"
                            >
                                <p
                                    class="text-xs font-semibold uppercase
                                           tracking-wide text-gray-500"
                                >
                                    Kendaraan Resmi
                                </p>

                                <p
                                    class="mt-2 text-3xl font-bold
                                           text-gray-900"
                                >
                                    {{
                                        number_format(
                                            $officialVehicleCount,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Sumber PC SET UTAMA
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-indigo-200
                                       bg-indigo-50 p-5 shadow-sm"
                            >
                                <p
                                    class="text-xs font-semibold uppercase
                                           tracking-wide text-indigo-700"
                                >
                                    Kendaraan P1
                                </p>

                                <p
                                    class="mt-2 text-3xl font-bold
                                           text-indigo-900"
                                >
                                    {{
                                        number_format(
                                            $p1VehicleCount,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-indigo-700">
                                    Tujuan fleksibel, tanpa profil jarak
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-blue-200
                                       bg-blue-50 p-5 shadow-sm"
                            >
                                <p
                                    class="text-xs font-semibold uppercase
                                           tracking-wide text-blue-700"
                                >
                                    Kendaraan P2
                                </p>

                                <p
                                    class="mt-2 text-3xl font-bold
                                           text-blue-900"
                                >
                                    {{
                                        number_format(
                                            $p2VehicleCount,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-blue-700">
                                    SPBE tujuan tetap dan profil jarak
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-amber-200
                                       bg-amber-50 p-5 shadow-sm"
                            >
                                <p
                                    class="text-xs font-semibold uppercase
                                           tracking-wide text-amber-700"
                                >
                                    Konflik P1 Diselesaikan
                                </p>

                                <p
                                    class="mt-2 text-3xl font-bold
                                           text-amber-900"
                                >
                                    {{
                                        number_format(
                                            $p1DuplicatesResolved
                                            +
                                            $p1ConflictsResolved,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-amber-700">
                                    Mengikuti PC SET UTAMA
                                </p>
                            </div>
                        </div>

                        @if(!$p1SheetFound)
                            <div
                                class="rounded-xl border border-yellow-200
                                       bg-yellow-50 px-5 py-4
                                       text-sm text-yellow-900"
                            >
                                Sheet <strong>KENDARAAN P1</strong>
                                tidak ditemukan. Semua kendaraan resmi
                                akan diperlakukan sebagai P2.
                            </div>
                        @elseif($p1DuplicatesResolved > 0)
                            <div
                                class="rounded-xl border border-blue-200
                                       bg-blue-50 px-5 py-4
                                       text-sm text-blue-900"
                            >
                                Terdapat
                                <strong>
                                    {{ $p1DuplicatesResolved }}
                                </strong>
                                nopol duplikat pada sheet KENDARAAN P1.
                                Sistem telah memilih data yang konsisten
                                dengan PC SET UTAMA.
                            </div>
                        @endif
                    @endif

                    {{-- Judul preview sheet --}}
                    <div
                        class="flex flex-col justify-between gap-3
                               rounded-xl border border-gray-200
                               bg-white px-5 py-4 shadow-sm
                               md:flex-row md:items-center"
                    >
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">
                                Preview Isi Workbook
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Klik nama sheet untuk membuka atau
                                menutup tabel preview.
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-gray-100
                                   px-3 py-1.5 text-xs font-semibold
                                   text-gray-700"
                        >
                            {{ $preview['sheet_count'] }} sheet
                        </span>
                    </div>

                    {{-- Preview seluruh sheet --}}
                    @foreach($preview['sheets'] as $sheet)
                        @php
                            $sourceLabels = [
                                'master_terminal' =>
                                    'Calon sumber Master TLPG',

                                'master_company' =>
                                    'Calon sumber Master SPBE',

                                'master_vehicle' =>
                                    'Calon sumber kendaraan/nopol',

                                'rotation_result' =>
                                    'Hasil rotasi/grouping',

                                'calculation_helper' =>
                                    'Sheet bantu perhitungan',
                            ];

                            $sourceLabel =
                                $sourceLabels[
                                    $sheet['likely_source']
                                ]
                                ?? null;

                            $importantSources = [
                                'master_terminal',
                                'master_company',
                                'master_vehicle',
                                'rotation_result',
                            ];

                            $openByDefault =
                                in_array(
                                    $sheet['likely_source'],
                                    $importantSources,
                                    true
                                );
                        @endphp

                        <details
                            class="overflow-hidden rounded-xl
                                   border border-gray-200
                                   bg-white shadow-sm"
                            @if($openByDefault)
                                open
                            @endif
                        >
                            <summary
                                class="cursor-pointer list-none
                                       border-b border-gray-200
                                       bg-gray-50 px-5 py-4"
                            >
                                <div
                                    class="flex flex-col justify-between
                                           gap-4 lg:flex-row
                                           lg:items-center"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap
                                                   items-center gap-2"
                                        >
                                            <h3
                                                class="break-words
                                                       text-lg font-bold
                                                       text-gray-900"
                                            >
                                                {{ $sheet['name'] }}
                                            </h3>

                                            @if(!$sheet['is_visible'])
                                                <span
                                                    class="rounded-full
                                                           bg-gray-200
                                                           px-2.5 py-1
                                                           text-xs font-semibold
                                                           text-gray-700"
                                                >
                                                    Hidden
                                                </span>
                                            @endif

                                            @if($sourceLabel)
                                                <span
                                                    class="rounded-full
                                                           bg-blue-100
                                                           px-2.5 py-1
                                                           text-xs font-semibold
                                                           text-blue-700"
                                                >
                                                    {{ $sourceLabel }}
                                                </span>
                                            @endif
                                        </div>

                                        <p
                                            class="mt-1 text-xs
                                                   text-gray-500"
                                        >
                                            Area data:
                                            {{ $sheet['highest_column'] }}
                                            {{ $sheet['highest_row'] }}

                                            <span class="mx-1">·</span>

                                            {{ $sheet['highest_row'] }}
                                            baris

                                            <span class="mx-1">·</span>

                                            {{
                                                $sheet[
                                                    'highest_column_index'
                                                ]
                                            }}
                                            kolom
                                        </p>
                                    </div>

                                    <div
                                        class="grid shrink-0
                                               grid-cols-3 gap-2
                                               text-center"
                                    >
                                        <div
                                            class="rounded-lg border
                                                   border-gray-200
                                                   bg-white px-3 py-2"
                                        >
                                            <p class="text-[11px] text-gray-500">
                                                Sel Berisi
                                            </p>

                                            <p class="font-bold text-gray-900">
                                                {{
                                                    number_format(
                                                        $sheet[
                                                            'statistics'
                                                        ][
                                                            'non_empty_count'
                                                        ],
                                                        0,
                                                        ',',
                                                        '.'
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg border
                                                   border-gray-200
                                                   bg-white px-3 py-2"
                                        >
                                            <p class="text-[11px] text-gray-500">
                                                Formula
                                            </p>

                                            <p class="font-bold text-blue-700">
                                                {{
                                                    number_format(
                                                        $sheet[
                                                            'statistics'
                                                        ][
                                                            'formula_count'
                                                        ],
                                                        0,
                                                        ',',
                                                        '.'
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-lg border
                                                   border-gray-200
                                                   bg-white px-3 py-2"
                                        >
                                            <p class="text-[11px] text-gray-500">
                                                Error
                                            </p>

                                            <p
                                                class="font-bold
                                                       {{
                                                           $sheet[
                                                               'statistics'
                                                           ][
                                                               'error_count'
                                                           ] > 0
                                                               ? 'text-red-700'
                                                               : 'text-green-700'
                                                       }}"
                                            >
                                                {{
                                                    number_format(
                                                        $sheet[
                                                            'statistics'
                                                        ][
                                                            'error_count'
                                                        ],
                                                        0,
                                                        ',',
                                                        '.'
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </summary>

                            @if($sheet['preview_truncated'])
                                <div
                                    class="border-b border-yellow-200
                                           bg-yellow-50 px-5 py-3
                                           text-xs text-yellow-800"
                                >
                                    Preview dibatasi maksimal 25 baris
                                    dan 20 kolom pertama.
                                    Data asli tidak diubah.
                                </div>
                            @endif

                            <div class="max-h-[42rem] overflow-auto">
                                <table
                                    class="min-w-max border-collapse
                                           text-xs"
                                >
                                    <thead
                                        class="sticky top-0 z-20
                                               bg-gray-100"
                                    >
                                        <tr>
                                            <th
                                                class="sticky left-0 z-30
                                                       border border-gray-200
                                                       bg-gray-100
                                                       px-3 py-2
                                                       text-gray-500"
                                            >
                                                #
                                            </th>

                                            @foreach(
                                                $sheet['headers']
                                                as $columnLetter
                                            )
                                                <th
                                                    class="min-w-40
                                                           border
                                                           border-gray-200
                                                           px-3 py-2
                                                           text-left
                                                           font-bold
                                                           text-gray-700"
                                                >
                                                    {{ $columnLetter }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($sheet['rows'] as $row)
                                            <tr>
                                                <td
                                                    class="sticky left-0
                                                           z-10 border
                                                           border-gray-200
                                                           bg-gray-50
                                                           px-3 py-2
                                                           text-center
                                                           font-semibold
                                                           text-gray-500"
                                                >
                                                    {{ $row['row_number'] }}
                                                </td>

                                                @foreach(
                                                    $sheet['headers']
                                                    as $columnLetter
                                                )
                                                    @php
                                                        $cell =
                                                            $row['cells'][
                                                                $columnLetter
                                                            ];
                                                    @endphp

                                                    <td
                                                        class="max-w-80
                                                               border
                                                               border-gray-200
                                                               px-3 py-2
                                                               align-top
                                                               {{
                                                                   $cell[
                                                                       'is_error'
                                                                   ]
                                                                       ? 'bg-red-50'
                                                                       : (
                                                                           $cell[
                                                                               'is_formula'
                                                                           ]
                                                                               ? 'bg-blue-50'
                                                                               : 'bg-white'
                                                                       )
                                                               }}"
                                                        title="{{
                                                            $cell[
                                                                'is_formula'
                                                            ]
                                                                ? $cell[
                                                                    'formula'
                                                                ]
                                                                : $cell[
                                                                    'raw'
                                                                ]
                                                        }}"
                                                    >
                                                        <div
                                                            class="max-h-24
                                                                   overflow-auto
                                                                   whitespace-pre-wrap
                                                                   break-words"
                                                        >
                                                            {{
                                                                $cell[
                                                                    'display'
                                                                ]
                                                            }}
                                                        </div>

                                                        @if(
                                                            $cell[
                                                                'is_formula'
                                                            ]
                                                        )
                                                            <span
                                                                class="mt-1
                                                                       inline-block
                                                                       rounded
                                                                       bg-blue-100
                                                                       px-1.5
                                                                       py-0.5
                                                                       text-[10px]
                                                                       font-semibold
                                                                       text-blue-700"
                                                            >
                                                                Formula
                                                            </span>
                                                        @endif

                                                        @if(
                                                            $cell[
                                                                'calculation_failed'
                                                            ]
                                                        )
                                                            <span
                                                                class="mt-1
                                                                       inline-block
                                                                       rounded
                                                                       bg-red-100
                                                                       px-1.5
                                                                       py-0.5
                                                                       text-[10px]
                                                                       font-semibold
                                                                       text-red-700"
                                                            >
                                                                Tidak dapat dihitung
                                                            </span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if(
                                count(
                                    $sheet[
                                        'statistics'
                                    ][
                                        'formula_examples'
                                    ]
                                ) > 0
                            )
                                <div
                                    class="border-t border-gray-200
                                           px-5 py-4"
                                >
                                    <details>
                                        <summary
                                            class="cursor-pointer
                                                   font-semibold
                                                   text-blue-700"
                                        >
                                            Contoh Formula
                                            ({{
                                                count(
                                                    $sheet[
                                                        'statistics'
                                                    ][
                                                        'formula_examples'
                                                    ]
                                                )
                                            }})
                                        </summary>

                                        <div
                                            class="mt-3 max-h-80
                                                   overflow-auto
                                                   rounded-lg border"
                                        >
                                            <table
                                                class="min-w-full
                                                       divide-y text-xs"
                                            >
                                                <thead
                                                    class="sticky top-0
                                                           bg-gray-50"
                                                >
                                                    <tr>
                                                        <th
                                                            class="px-3 py-2
                                                                   text-left"
                                                        >
                                                            Sel
                                                        </th>

                                                        <th
                                                            class="px-3 py-2
                                                                   text-left"
                                                        >
                                                            Formula
                                                        </th>

                                                        <th
                                                            class="px-3 py-2
                                                                   text-left"
                                                        >
                                                            Hasil
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody class="divide-y">
                                                    @foreach(
                                                        $sheet[
                                                            'statistics'
                                                        ][
                                                            'formula_examples'
                                                        ]
                                                        as $formula
                                                    )
                                                        <tr>
                                                            <td
                                                                class="px-3
                                                                       py-2
                                                                       font-semibold"
                                                            >
                                                                {{
                                                                    $formula[
                                                                        'coordinate'
                                                                    ]
                                                                }}
                                                            </td>

                                                            <td
                                                                class="px-3
                                                                       py-2
                                                                       font-mono"
                                                            >
                                                                {{
                                                                    $formula[
                                                                        'formula'
                                                                    ]
                                                                }}
                                                            </td>

                                                            <td
                                                                class="px-3
                                                                       py-2"
                                                            >
                                                                {{
                                                                    $formula[
                                                                        'result'
                                                                    ]
                                                                }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </div>
                            @endif

                            @if(
                                count(
                                    $sheet[
                                        'statistics'
                                    ][
                                        'error_examples'
                                    ]
                                ) > 0
                            )
                                <div
                                    class="border-t border-red-200
                                           bg-red-50 px-5 py-4"
                                >
                                    <details open>
                                        <summary
                                            class="cursor-pointer
                                                   font-bold
                                                   text-red-700"
                                        >
                                            Contoh Error
                                            ({{
                                                count(
                                                    $sheet[
                                                        'statistics'
                                                    ][
                                                        'error_examples'
                                                    ]
                                                )
                                            }})
                                        </summary>

                                        <div class="mt-3 space-y-2">
                                            @foreach(
                                                $sheet[
                                                    'statistics'
                                                ][
                                                    'error_examples'
                                                ]
                                                as $error
                                            )
                                                <div
                                                    class="rounded-lg
                                                           border
                                                           border-red-200
                                                           bg-white p-3
                                                           text-xs"
                                                >
                                                    <span class="font-bold">
                                                        {{
                                                            $error[
                                                                'coordinate'
                                                            ]
                                                        }}
                                                    </span>

                                                    <span class="ms-2">
                                                        {{
                                                            $error[
                                                                'value'
                                                            ]
                                                        }}
                                                    </span>

                                                    @if($error['formula'])
                                                        <div
                                                            class="mt-1
                                                                   font-mono
                                                                   text-red-700"
                                                        >
                                                            {{
                                                                $error[
                                                                    'formula'
                                                                ]
                                                            }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </div>
                            @endif
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>