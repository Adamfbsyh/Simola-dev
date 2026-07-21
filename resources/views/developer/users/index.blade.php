<x-app-layout>
    <x-slot name="header">
        <div class="access-header">
            <div>
                <h2>Manajemen Pengguna</h2>

                <p>
                    Membuat akun serta mengatur fitur yang dapat diakses.
                </p>
            </div>

            <a
                href="{{ route('developer.users.create') }}"
                class="button-primary"
            >
                + Tambah Pengguna
            </a>
        </div>
    </x-slot>

    <style>
        .access-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .access-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .access-header h2 {
            margin: 0;
            color: #111827;
            font-size: 22px;
            font-weight: 800;
        }

        .access-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .button-primary,
        .button-edit,
        .button-status,
        .button-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .button-primary {
            padding: 10px 15px;
            background: #3730a3;
            color: #ffffff;
        }

        .button-edit {
            padding: 8px 12px;
            background: #dbeafe;
            color: #1e40af;
        }

        .button-status {
            padding: 8px 12px;
            background: #fef3c7;
            color: #92400e;
        }

        .filter-card,
        .table-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .filter-card {
            margin-bottom: 18px;
            padding: 17px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) 180px 180px auto;
            gap: 12px;
            align-items: end;
        }

        .field-label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 12px;
            font-weight: 800;
        }

        .field-control {
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
        }

        .filter-submit {
            padding: 10px 15px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .button-reset {
            padding: 10px 15px;
            background: #6b7280;
            color: #ffffff;
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .alert-success {
            border: 1px solid #86efac;
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            border: 1px solid #fca5a5;
            background: #fee2e2;
            color: #991b1b;
        }

        .table-card {
            overflow: hidden;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .access-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .access-table th {
            padding: 11px 12px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #374151;
            font-size: 12px;
            font-weight: 800;
            text-align: left;
            white-space: nowrap;
        }

        .access-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: middle;
        }

        .access-table tbody tr:hover {
            background: #f9fafb;
        }

        .user-name {
            color: #111827;
            font-weight: 800;
        }

        .user-email {
            margin-top: 3px;
            color: #6b7280;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .badge-developer {
            background: #ede9fe;
            color: #5b21b6;
        }

        .badge-spv {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-lead {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-group {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .pagination-wrapper {
            padding: 16px;
        }

        .empty-state {
            padding: 28px !important;
            color: #6b7280 !important;
            text-align: center;
        }

        @media (max-width: 850px) {
            .access-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 560px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="access-wrapper">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <form
            method="GET"
            action="{{ route('developer.users.index') }}"
            class="filter-card"
        >
            <div class="filter-grid">
                <div>
                    <label class="field-label">
                        Cari Pengguna
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Nama atau email"
                        class="field-control"
                    >
                </div>

                <div>
                    <label class="field-label">
                        Role
                    </label>

                    <select
                        name="role"
                        class="field-control"
                    >
                        <option value="">
                            Semua Role
                        </option>

                        @foreach($roles as $role)
                            <option
                                value="{{ $role->name }}"
                                @selected(
                                    $roleFilter === $role->name
                                )
                            >
                                {{ strtoupper($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="field-control"
                    >
                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="active"
                            @selected($statusFilter === 'active')
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            @selected($statusFilter === 'inactive')
                        >
                            Nonaktif
                        </option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button
                        type="submit"
                        class="filter-submit"
                    >
                        Terapkan
                    </button>

                    <a
                        href="{{ route('developer.users.index') }}"
                        class="button-reset"
                    >
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <div class="table-card">
            <div class="table-responsive">
                <table class="access-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Permission Langsung</th>
                            <th>Status</th>
                            <th>Terakhir Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $item)
                            @php
                                $roleName =
                                    $item->roles->first()?->name
                                    ?? '-';

                                $roleClass = match ($roleName) {
                                    'developer' => 'badge-developer',
                                    'spv' => 'badge-spv',
                                    'lead' => 'badge-lead',
                                    default => 'badge-lead',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <div class="user-name">
                                        {{ $item->name }}
                                    </div>

                                    <div class="user-email">
                                        {{ $item->email }}
                                    </div>
                                </td>

                                <td>
                                    <span class="badge {{ $roleClass }}">
                                        {{ strtoupper($roleName) }}
                                    </span>
                                </td>

                                <td>
                                    @if($roleName === 'developer')
                                        Semua fitur
                                    @else
                                        {{ number_format(
                                            $item->permissions_count
                                        ) }} izin
                                    @endif
                                </td>

                                <td>
                                    <span class="badge {{
                                        $item->is_active
                                            ? 'badge-active'
                                            : 'badge-inactive'
                                    }}">
                                        {{
                                            $item->is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </span>
                                </td>

                                <td>
                                    {{
                                        $item->last_login_at
                                            ? $item->last_login_at
                                                ->format('d-m-Y H:i')
                                            : '-'
                                    }}
                                </td>

                                <td>
                                    <div class="action-group">
                                        <a
                                            href="{{ route(
                                                'developer.users.edit',
                                                $item
                                            ) }}"
                                            class="button-edit"
                                        >
                                            Atur Akses
                                        </a>

                                        @if(!auth()->user()->is($item))
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'developer.users.toggle-active',
                                                    $item
                                                ) }}"
                                                onsubmit="return confirm(
                                                    'Ubah status akun ini?'
                                                )"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="button-status"
                                                >
                                                    {{
                                                        $item->is_active
                                                            ? 'Nonaktifkan'
                                                            : 'Aktifkan'
                                                    }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="empty-state"
                                >
                                    Belum ada pengguna yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrapper">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>