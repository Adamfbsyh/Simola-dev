@php
    $checkedPermissions = old(
        'permissions',
        $selectedPermissions
    );

    $currentRole = old(
        'role',
        $selectedRole
    );

    $currentActive = (string) old(
        'is_active',
        $user->is_active ? '1' : '0'
    );
@endphp

<style>
    .form-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .form-card,
    .permission-card {
        border: 1px solid #e5e7eb;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .form-card {
        margin-bottom: 18px;
        padding: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .field-full {
        grid-column: 1 / -1;
    }

    .field-label {
        display: block;
        margin-bottom: 7px;
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

    .field-help {
        margin-top: 5px;
        color: #6b7280;
        font-size: 11px;
    }

    .field-error {
        margin-top: 5px;
        color: #b91c1c;
        font-size: 11px;
        font-weight: 700;
    }

    .permission-card {
        overflow: hidden;
    }

    .permission-main-header {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .permission-main-header h3 {
        margin: 0;
        color: #111827;
        font-size: 17px;
        font-weight: 800;
    }

    .permission-main-header p {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 12px;
    }

    .global-actions {
        display: flex;
        gap: 8px;
    }

    .small-button {
        padding: 8px 11px;
        border: 1px solid #c7d2fe;
        border-radius: 7px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
    }

    .permission-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        padding: 18px;
    }

    .permission-module {
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        overflow: hidden;
    }

    .module-header {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        padding: 11px 13px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .module-header strong {
        color: #111827;
        font-size: 13px;
    }

    .module-select {
        color: #4338ca;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
    }

    .permission-list {
        padding: 8px 13px;
    }

    .permission-item {
        display: flex;
        gap: 9px;
        align-items: flex-start;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .permission-item:last-child {
        border-bottom: 0;
    }

    .permission-item input {
        margin-top: 2px;
    }

    .permission-label {
        color: #374151;
        font-size: 12px;
        line-height: 1.4;
    }

    .permission-code {
        display: block;
        margin-top: 2px;
        color: #9ca3af;
        font-size: 10px;
    }

    .developer-note {
        margin: 16px 18px 0;
        padding: 12px;
        border: 1px solid #c4b5fd;
        border-radius: 9px;
        background: #f5f3ff;
        color: #5b21b6;
        font-size: 12px;
        font-weight: 700;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 18px 20px;
        border-top: 1px solid #e5e7eb;
    }

    .button-save,
    .button-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }

    .button-save {
        border: 0;
        background: #3730a3;
        color: #ffffff;
        cursor: pointer;
    }

    .button-back {
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

    @media (max-width: 800px) {
        .form-grid,
        .permission-grid {
            grid-template-columns: 1fr;
        }

        .permission-main-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="form-wrapper">
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

    @if($errors->any())
        <div class="alert alert-error">
            <strong>Periksa kembali data berikut:</strong>

            <ul style="margin:8px 0 0 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <div class="form-grid">
            <div>
                <label
                    for="name"
                    class="field-label"
                >
                    Nama Pengguna
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    class="field-control"
                    required
                >
            </div>

            <div>
                <label
                    for="email"
                    class="field-label"
                >
                    Email Login
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    class="field-control"
                    required
                >
            </div>

            <div>
                <label
                    for="role"
                    class="field-label"
                >
                    Role
                </label>

                <select
                    id="role"
                    name="role"
                    class="field-control"
                    required
                >
                    @foreach($roles as $role)
                        <option
                            value="{{ $role->name }}"
                            @selected(
                                $currentRole === $role->name
                            )
                        >
                            {{ strtoupper($role->name) }}
                        </option>
                    @endforeach
                </select>

                <div class="field-help">
                    Role berfungsi sebagai identitas akun.
                    Akses fitur ditentukan melalui checkbox di bawah.
                </div>
            </div>

            <div>
                <label
                    for="is_active"
                    class="field-label"
                >
                    Status Akun
                </label>

                <select
                    id="is_active"
                    name="is_active"
                    class="field-control"
                >
                    <option
                        value="1"
                        @selected($currentActive === '1')
                    >
                        Aktif
                    </option>

                    <option
                        value="0"
                        @selected($currentActive === '0')
                    >
                        Nonaktif
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="password"
                    class="field-label"
                >
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="field-control"
                    @if(!$user->exists) required @endif
                >

                <div class="field-help">
                    @if($user->exists)
                        Kosongkan apabila password tidak diubah.
                    @else
                        Minimal 8 karakter.
                    @endif
                </div>
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="field-label"
                >
                    Konfirmasi Password
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="field-control"
                    @if(!$user->exists) required @endif
                >
            </div>
        </div>
    </div>

    <div class="permission-card">
        <div class="permission-main-header">
            <div>
                <h3>Hak Akses Fitur</h3>

                <p>
                    Centang fitur dan tindakan yang boleh digunakan akun ini.
                </p>
            </div>

            <div class="global-actions">
                <button
                    type="button"
                    class="small-button"
                    onclick="selectAllPermissions(true)"
                >
                    Pilih Semua
                </button>

                <button
                    type="button"
                    class="small-button"
                    onclick="selectAllPermissions(false)"
                >
                    Kosongkan
                </button>
            </div>
        </div>

        <div
            id="developer-note"
            class="developer-note"
            style="{{ $currentRole === 'developer'
                ? ''
                : 'display:none;'
            }}"
        >
            Role Developer selalu memiliki akses penuh melalui
            superuser. Checkbox di bawah tidak membatasi Developer.
        </div>

        <div class="permission-grid">
            @foreach($modules as $moduleKey => $module)
                <div
                    class="permission-module"
                    data-module="{{ $moduleKey }}"
                >
                    <div class="module-header">
                        <strong>
                            {{ $module['label'] ?? $moduleKey }}
                        </strong>

                        <button
                            type="button"
                            class="module-select"
                            onclick="toggleModule(
                                '{{ $moduleKey }}'
                            )"
                        >
                            Pilih Modul
                        </button>
                    </div>

                    <div class="permission-list">
                        @foreach(
                            $module['permissions'] ?? []
                            as $permissionName => $permissionLabel
                        )
                            <label class="permission-item">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permissionName }}"
                                    data-permission-module="{{ $moduleKey }}"
                                    @checked(
                                        in_array(
                                            $permissionName,
                                            $checkedPermissions,
                                            true
                                        )
                                    )
                                >

                                <span class="permission-label">
                                    {{ $permissionLabel }}

                                    <span class="permission-code">
                                        {{ $permissionName }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="form-actions">
            <a
                href="{{ route('developer.users.index') }}"
                class="button-back"
            >
                Kembali
            </a>

            <button
                type="submit"
                class="button-save"
            >
                Simpan Akun dan Hak Akses
            </button>
        </div>
    </div>
</div>

<script>
    function permissionCheckboxes() {
        return Array.from(
            document.querySelectorAll(
                'input[name="permissions[]"]'
            )
        );
    }

    function selectAllPermissions(checked) {
        permissionCheckboxes().forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }

    function toggleModule(moduleName) {
        const checkboxes = Array.from(
            document.querySelectorAll(
                '[data-permission-module="' +
                moduleName +
                '"]'
            )
        );

        const allChecked = checkboxes.every(function (checkbox) {
            return checkbox.checked;
        });

        checkboxes.forEach(function (checkbox) {
            checkbox.checked = !allChecked;
        });
    }

    document
        .getElementById('role')
        .addEventListener(
            'change',
            function () {
                const note = document.getElementById(
                    'developer-note'
                );

                note.style.display =
                    this.value === 'developer'
                        ? 'block'
                        : 'none';
            }
        );
</script>