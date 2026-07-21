<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tambah Pengguna
            </h2>

            <p style="margin-top:5px; color:#6b7280; font-size:13px;">
                Membuat akun SPV, Lead, atau Developer baru.
            </p>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route('developer.users.store') }}"
    >
        @csrf

        @include(
            'developer.users._form'
        )
    </form>
</x-app-layout>