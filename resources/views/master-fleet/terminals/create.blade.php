<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900">
            Tambah TLPG / Terminal
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route(
                    'master-fleet.terminals.store'
                ) }}"
                class="rounded-xl border bg-white p-6 shadow-sm"
            >
                @csrf

                @include(
                    'master-fleet.terminals._form'
                )

                <div class="mt-6 flex justify-end gap-3">
                    <a
                        href="{{ route(
                            'master-fleet.terminals.index'
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
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>