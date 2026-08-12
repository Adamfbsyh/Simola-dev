@php
    $currentUser = auth()->user();

    /*
     * Logo diarahkan ke halaman pertama yang dapat diakses.
     */
    if ($currentUser->can('dashboard.view')) {
        $homeUrl = route('dashboard');
    } elseif ($currentUser->can('upload.view')) {
        $homeUrl = route('upload-terpadu.index');
    } elseif ($currentUser->can('upload.history')) {
        $homeUrl = route('upload-terpadu.files');
    } elseif ($currentUser->can('crosscheck.view')) {
        $homeUrl = route('k32.index');
    } elseif ($currentUser->can('laporan-k32.view')) {
        $homeUrl = route('k32-report.index');
    } elseif (config('master-fleet.enabled') && $currentUser->can('master-fleet.view')) {
        $homeUrl =route('master-fleet.index');
    } elseif ($currentUser->can('users.access')) {
        $homeUrl = route('developer.users.index');
    } else {
        $homeUrl = route('profile.edit');
    }
@endphp

<nav
    x-data="{ open: false }"
    class="bg-white border-b border-gray-100 simola-navbar-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ $homeUrl }}">
                        <x-application-logo
                            class="block h-9 w-auto fill-current text-gray-800"
                        />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @can('dashboard.view')
                        <x-nav-link
                            :href="route('dashboard')"
                            :active="request()->routeIs('dashboard')"
                        >
                            Dashboard
                        </x-nav-link>
                    @endcan

                    @can('upload.view')
                        <x-nav-link
                            :href="route('upload-terpadu.index')"
                            :active="request()->routeIs('upload-terpadu.index')"
                        >
                            Upload Terpadu
                        </x-nav-link>
                    @endcan

                    @can('upload.history')
                        <x-nav-link
                            :href="route('upload-terpadu.files')"
                            :active="request()->routeIs(
                                'upload-terpadu.files',
                                'upload-terpadu.viewer',
                                'upload-terpadu.preview',
                                'upload-terpadu.download'
                            )"
                        >
                            Riwayat Upload
                        </x-nav-link>
                    @endcan

                    @can('crosscheck.view')
                        <x-nav-link
                            :href="route('k32.index')"
                            :active="request()->routeIs('k32.*')"
                        >
                            Crosscheck K3.2
                        </x-nav-link>
                    @endcan

                    @can('laporan-k32.view')
                        <x-nav-link
                            :href="route('k32-report.index')"
                            :active="request()->routeIs('k32-report.*')"
                        >
                            Laporan K3.2
                        </x-nav-link>
                    @endcan

                    @if( config('master-fleet.enabled')
                    )
                        @can('master-fleet.view')
                            <x-nav-link
                                :href="route(
                                    'master-fleet.index'
                                )"
                                :active="request()->routeIs(
                                    'master-fleet.*'
                                )"
                            >
                                Master Fleet
                            </x-nav-link>
<!-- SIMOLA_OPERATOR_CHAT_TOP_DEVICE_V20_START -->
                    <x-operator-chat-nav-link />
                    <!-- SIMOLA_OPERATOR_CHAT_TOP_DEVICE_V20_END -->

@endcan
                    @endif
                </div>
            </div>

            
                <div class="hidden sm:flex sm:items-center sm:ms-4">
                    @include('layouts.partials.theme-toggle')
                </div>
<div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown
                    align="right"
                    width="48"
                >
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2
                                   border border-transparent text-sm
                                   leading-4 font-medium rounded-md
                                   text-gray-500 bg-white hover:text-gray-700
                                   focus:outline-none transition
                                   ease-in-out duration-150"
                        >
                            <div>
                                {{ Auth::user()->name }}
                            </div>

                            <div class="ms-1">
                                <svg
                                    class="fill-current h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10
                                           10.586l3.293-3.293a1 1 0
                                           111.414 1.414l-4 4a1 1 0
                                           01-1.414 0l-4-4a1 1 0
                                           010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link
                            :href="route('profile.edit')"
                        >
                            Profile
                        </x-dropdown-link>

                        @can('users.access')
                            <x-dropdown-link
                                :href="route('developer.users.index')"
                            >
                                Manajemen Pengguna
                            </x-dropdown-link>
                        @endcan

                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                        >
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="
                                    event.preventDefault();
                                    this.closest('form').submit();
                                "
                            >
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2
                           rounded-md text-gray-400 hover:text-gray-500
                           hover:bg-gray-100 focus:outline-none
                           focus:bg-gray-100 focus:text-gray-500
                           transition duration-150 ease-in-out"
                >
                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            :class="{
                                'hidden': open,
                                'inline-flex': ! open
                            }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />

                        <path
                            :class="{
                                'hidden': ! open,
                                'inline-flex': open
                            }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div
        :class="{
            'block': open,
            'hidden': ! open
        }"
        class="hidden sm:hidden"
    >
        <!-- Mobile theme toggle -->
        <div class="border-t border-gray-200 pb-3 pt-3 sm:hidden">
            <div class="px-4">
                @include('layouts.partials.theme-toggle')
            </div>
        </div>

        <div class="pt-2 pb-3 space-y-1">
            @can('dashboard.view')
                <x-responsive-nav-link
                    :href="route('dashboard')"
                    :active="request()->routeIs('dashboard')"
                >
                    Dashboard
                </x-responsive-nav-link>
            @endcan

            @can('upload.view')
                <x-responsive-nav-link
                    :href="route('upload-terpadu.index')"
                    :active="request()->routeIs('upload-terpadu.index')"
                >
                    Upload Terpadu
                </x-responsive-nav-link>
            @endcan

            @can('upload.history')
                <x-responsive-nav-link
                    :href="route('upload-terpadu.files')"
                    :active="request()->routeIs(
                        'upload-terpadu.files',
                        'upload-terpadu.viewer',
                        'upload-terpadu.preview',
                        'upload-terpadu.download'
                    )"
                >
                    Riwayat Upload
                </x-responsive-nav-link>
            @endcan

            @can('crosscheck.view')
                <x-responsive-nav-link
                    :href="route('k32.index')"
                    :active="request()->routeIs('k32.*')"
                >
                    Crosscheck K3.2
                </x-responsive-nav-link>
            @endcan

            @can('laporan-k32.view')
                <x-responsive-nav-link
                    :href="route('k32-report.index')"
                    :active="request()->routeIs('k32-report.*')"
                >
                    Laporan K3.2
                </x-responsive-nav-link>
            @endcan

                        @if(
                config(
                    'master-fleet.enabled'
                )
            )
                @can('master-fleet.view')
                    <x-responsive-nav-link
                        :href="route(
                            'master-fleet.index'
                        )"
                        :active="request()->routeIs(
                            'master-fleet.*'
                        )"
                    >
                        Master Fleet
                    </x-responsive-nav-link>
                @endcan
            @endif

            @can('users.access')
                <x-responsive-nav-link
                    :href="route('developer.users.index')"
                    :active="request()->routeIs('developer.users.*')"
                >
                    Manajemen Pengguna
                </x-responsive-nav-link>
            @endcan
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">
                    {{ Auth::user()->name }}
                </div>

                <div class="font-medium text-sm text-gray-500">
                    {{ Auth::user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link
                    :href="route('profile.edit')"
                >
                    Profile
                </x-responsive-nav-link>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="
                            event.preventDefault();
                            this.closest('form').submit();
                        "
                    >
                        Log Out
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
    @include('layouts.partials.simola-nav-dropdown-enhancer')
</nav>