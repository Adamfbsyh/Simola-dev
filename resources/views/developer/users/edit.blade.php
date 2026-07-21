<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Atur Pengguna dan Hak Akses
            </h2>

            <p style="margin-top:5px; color:#6b7280; font-size:13px;">
                {{ $user->name }} — {{ $user->email }}
            </p>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route(
            'developer.users.update',
            $user
        ) }}"
    >
        @csrf
        @method('PUT')

        @include(
            'developer.users._form'
        )
    </form>
</x-app-layout>