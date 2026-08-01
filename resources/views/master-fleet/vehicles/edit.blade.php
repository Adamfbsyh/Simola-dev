<x-app-layout>
    <x-slot name="header">
        <div
            class="flex flex-col justify-between gap-3
                   sm:flex-row sm:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Edit Kendaraan
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $vehicle->plate_number }}
                </p>
            </div>

            <a
                href="{{ route(
                    'master-fleet.vehicles.history',
                    $vehicle
                ) }}"
                class="rounded-lg border border-gray-300 bg-white
                       px-4 py-2 text-sm font-semibold text-gray-700
                       shadow-sm hover:bg-gray-50"
            >
                Lihat Riwayat Nopol
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div
                    class="rounded-xl border border-green-200
                           bg-green-50 px-5 py-4 text-sm text-green-800"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4 text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4 text-sm text-red-800"
                >
                    <ul class="list-disc space-y-1 ps-5">
                        @foreach($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section
                class="rounded-xl border border-gray-200
                       bg-white p-6 shadow-sm"
            >
                <form
                    method="POST"
                    action="{{ route(
                        'master-fleet.vehicles.update',
                        $vehicle
                    ) }}"
                >
                    @csrf
                    @method('PUT')

                    @include(
                        'master-fleet.vehicles._form'
                    )

                    <div class="mt-6 flex justify-end gap-3">
                        <a
                            href="{{ route('master-fleet.vehicles.index') }}"
                            class="rounded-lg border border-gray-300
                                   px-5 py-2.5 text-sm font-semibold
                                   text-gray-700 hover:bg-gray-50"
                        >
                            Kembali
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5
                                   text-sm font-bold text-white
                                   hover:bg-blue-700"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </section>

            <section
                class="rounded-xl border border-gray-200
                       bg-white p-6 shadow-sm"
            >
                <h3 class="text-lg font-bold text-gray-900">
                    Posisi kendaraan saat ini
                </h3>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    Periode
                                </th>

                                <th class="px-4 py-3 text-left">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left">
                                    TLPG
                                </th>

                                <th class="px-4 py-3 text-left">
                                    Perusahaan
                                </th>

                                <th class="px-4 py-3 text-center">
                                    PC Lama
                                </th>

                                <th class="px-4 py-3 text-center">
                                    PC Final
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse(
                                $currentAssignments
                                as $assignment
                            )
                                <tr>
                                    <td class="px-4 py-3">
                                        {{
                                            $assignment
                                                ->groupingPeriod
                                                ?->name
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{
                                            $assignment
                                                ->groupingPeriod
                                                ?->status
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="px-4 py-3">
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

                                    <td class="px-4 py-3">
                                        {{
                                            $assignment
                                                ->company?->name
                                            ??
                                            $assignment
                                                ->company_name_snapshot
                                            ??
                                            '-'
                                        }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        {{
                                            $assignment->pc_initial
                                                ? 'PC '
                                                    .
                                                    $assignment
                                                        ->pc_initial
                                                : '-'
                                        }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        {{
                                            $assignment->pc_final
                                                ? 'PC '
                                                    .
                                                    $assignment
                                                        ->pc_final
                                                : '-'
                                        }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center
                                               text-gray-500"
                                    >
                                        Kendaraan belum masuk PC Set
                                        aktif maupun draft.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>