<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Upload Laporan Excel
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
            @if(session('error'))
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('laporan.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <label class="block mb-2 font-semibold">Pilih File Excel</label>

                <input type="file" name="file_excel" class="border p-2 w-full mb-4" required>

                @error('file_excel')
                    <p class="text-red-500 mb-3">{{ $message }}</p>
                @enderror

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Upload & Import
                </button>
            </form>
        </div>
    </div>
</x-app-layout>