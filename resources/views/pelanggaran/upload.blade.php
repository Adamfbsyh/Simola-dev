<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Upload Laporan Pelanggaran Harian
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">

            @if(session('error'))
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('pelanggaran.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block mb-2 font-semibold">Tanggal Laporan</label>
                    <input type="date" name="tanggal_laporan" class="border p-2 w-full" required>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 font-semibold">Periode</label>
                    <select name="periode" class="border p-2 w-full" required>
                        <option value="Harian">Harian</option>
                        <option value="Mingguan">Mingguan</option>
                        <option value="Bulanan">Bulanan</option>
                        <option value="Tahunan">Tahunan</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 font-semibold">File Excel</label>
                    <input type="file" name="file_excel" class="border p-2 w-full" required>
                    <p class="text-sm text-gray-500 mt-1">
                        Upload file Excel yang memiliki sheet <b>K3-02.2</b>.
                    </p>
                </div>

                @error('file_excel')
                    <p class="text-red-500 mb-3">{{ $message }}</p>
                @enderror

                <div class="mt-6">
                    <button type="submit" style="background:#2563eb; color:white; padding:10px 18px; border-radius:6px; font-weight:bold;">
                        Upload & Import
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>