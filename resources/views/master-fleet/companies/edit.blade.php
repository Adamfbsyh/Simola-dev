<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900">
            Edit SPBE / Perusahaan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-5 rounded-lg border border-green-200
                            bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route(
                    'master-fleet.companies.update',
                    $company
                ) }}"
                class="rounded-xl border bg-white p-6 shadow-sm"
            >
                @csrf
                @method('PUT')

                @include(
                    'master-fleet.companies._form'
                )

                <div class="mt-7 flex justify-end gap-3">
                    <a
                        href="{{ route(
                            'master-fleet.companies.index'
                        ) }}"
                        class="rounded-lg border px-4 py-2
                               text-sm font-semibold text-gray-700"
                    >
                        Kembali
                    </a>

                    <button
                        class="rounded-lg bg-blue-600 px-5 py-2
                               text-sm font-semibold text-white"
                    >
                        Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>