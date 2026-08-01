<x-app-layout>
    <x-slot name="header">
        <div
            class="flex flex-col justify-between gap-3
                   sm:flex-row sm:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    Riwayat Perubahan Nopol
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Nopol aktif: {{ $vehicle->plate_number }}
                </p>
            </div>

            <a
                href="{{ route(
                    'master-fleet.vehicles.edit',
                    $vehicle
                ) }}"
                class="rounded-lg border border-gray-300 bg-white
                       px-4 py-2 text-sm font-semibold text-gray-700
                       shadow-sm hover:bg-gray-50"
            >
                Kembali ke Edit
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section
                class="overflow-hidden rounded-xl border
                       border-gray-200 bg-white shadow-sm"
            >
                <div class="border-b px-6 py-5">
                    <h3 class="font-bold text-gray-900">
                        {{ $vehicle->plate_number }}
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            $vehicle->company?->name
                            ?? 'Perusahaan belum tersedia'
                        }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left">
                                    Tanggal
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Nopol Lama
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Nopol Baru
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Alasan
                                </th>

                                <th class="px-5 py-3 text-left">
                                    Diubah Oleh
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse(
                                $vehicle->plateHistories
                                as $history
                            )
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        {{
                                            optional(
                                                $history->effective_date
                                            )->format('d-m-Y')
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span
                                            class="rounded-full bg-red-50
                                                   px-3 py-1 font-semibold
                                                   text-red-700"
                                        >
                                            {{ $history->old_plate_number }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span
                                            class="rounded-full bg-green-50
                                                   px-3 py-1 font-semibold
                                                   text-green-700"
                                        >
                                            {{ $history->new_plate_number }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        {{ $history->reason ?: '-' }}
                                    </td>

                                    <td class="px-5 py-4">
                                        {{
                                            $history->changedBy?->name
                                            ?? 'Sistem'
                                        }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-5 py-12 text-center
                                               text-gray-500"
                                    >
                                        Belum pernah ada perubahan nopol.
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