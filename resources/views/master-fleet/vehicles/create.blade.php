<x-app-layout>
    @include('master-fleet.partials.fleet-type-selector')
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Tambah Kendaraan
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Tambahkan nopol baru ke Master Kendaraan.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if(session('error'))
                <div
                    class="mb-5 rounded-xl border border-red-200
                           bg-red-50 px-5 py-4 text-sm text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div
                    class="mb-5 rounded-xl border border-red-200
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

            <form
                method="POST"
                action="{{ route('master-fleet.vehicles.store') }}"
                class="rounded-xl border border-gray-200
                       bg-white p-6 shadow-sm"
            >
                @csrf

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
                        Simpan Kendaraan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>