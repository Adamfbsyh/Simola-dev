<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Data Laporan
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <a href="{{ route('laporan.upload') }}" class="bg-blue-600 text-white px-4 py-2 rounded inline-block mb-4">
                Upload Excel
            </a>

            <div class="overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">Tanggal</th>
                            <th class="border p-2">Periode</th>
                            <th class="border p-2">Unit</th>
                            <th class="border p-2">Kategori</th>
                            <th class="border p-2">Target</th>
                            <th class="border p-2">Realisasi</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Kendala</th>
                            <th class="border p-2">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($laporans as $laporan)
                            <tr>
                                <td class="border p-2">{{ $laporan->tanggal }}</td>
                                <td class="border p-2">{{ $laporan->periode }}</td>
                                <td class="border p-2">{{ $laporan->unit }}</td>
                                <td class="border p-2">{{ $laporan->kategori }}</td>
                                <td class="border p-2">{{ $laporan->target }}</td>
                                <td class="border p-2">{{ $laporan->realisasi }}</td>
                                <td class="border p-2">{{ $laporan->status }}</td>
                                <td class="border p-2">{{ $laporan->kendala }}</td>
                                <td class="border p-2">{{ $laporan->keterangan }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="border p-4 text-center">
                                    Belum ada data laporan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $laporans->links() }}
            </div>
        </div>
    </div>
</x-app-layout>