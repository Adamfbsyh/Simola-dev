<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class UserAccessController extends Controller
{
    /**
     * Daftar pengguna.
     */
    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        $roleFilter = trim(
            (string) $request->input(
                'role',
                ''
            )
        );

        $statusFilter = trim(
            (string) $request->input(
                'status',
                ''
            )
        );

        $users = User::query()
            ->with('roles')
            ->withCount('permissions')
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        function ($subQuery) use ($search) {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                $roleFilter !== '',
                function ($query) use ($roleFilter) {
                    $query->role(
                        $roleFilter
                    );
                }
            )
            ->when(
                $statusFilter === 'active',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->when(
                $statusFilter === 'inactive',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        false
                    )
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()
            ->where(
                'guard_name',
                'web'
            )
            ->orderBy('name')
            ->get();

        return view(
            'developer.users.index',
            compact(
                'users',
                'roles',
                'search',
                'roleFilter',
                'statusFilter'
            )
        );
    }

    /**
     * Form tambah akun.
     */
    public function create(): View
    {
        $user = new User([
            'is_active' => true,
        ]);

        return view(
            'developer.users.create',
            [
                'user' =>
                    $user,

                'roles' =>
                    $this->availableRoles(),

                'modules' =>
                    config(
                        'access.modules',
                        []
                    ),

                'selectedPermissions' =>
                    [],

                'selectedRole' =>
                    'lead',
            ]
        );
    }

    /**
     * Menyimpan akun baru.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate(
            $this->validationRules(
                null,
                true
            ),
            $this->validationMessages()
        );

        $permissions = $this
            ->sanitizePermissions(
                $validated['permissions']
                ?? []
            );

        try {
            $user = DB::transaction(
                function () use (
                    $validated,
                    $permissions
                ) {
                    $user = User::query()
                        ->create([
                            'name' =>
                                trim(
                                    $validated['name']
                                ),

                            'email' =>
                                mb_strtolower(
                                    trim(
                                        $validated['email']
                                    ),
                                    'UTF-8'
                                ),

                            'password' =>
                                Hash::make(
                                    $validated['password']
                                ),

                            'is_active' =>
                                (bool) $validated[
                                    'is_active'
                                ],
                        ]);

                    $user->syncRoles([
                        $validated['role'],
                    ]);

                    $user->syncPermissions(
                        $permissions
                    );

                    return $user;
                }
            );

            $this->clearPermissionCache();

            return redirect()
                ->route(
                    'developer.users.edit',
                    $user
                )
                ->with(
                    'success',
                    'Akun berhasil dibuat.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Akun gagal dibuat: ' .
                    $e->getMessage()
                );
        }
    }

    /**
     * Form edit akun dan permission.
     */
    public function edit(
        User $user
    ): View {
        $user->load([
            'roles',
            'permissions',
        ]);

        return view(
            'developer.users.edit',
            [
                'user' =>
                    $user,

                'roles' =>
                    $this->availableRoles(),

                'modules' =>
                    config(
                        'access.modules',
                        []
                    ),

                /*
                 * Hanya permission langsung milik akun.
                 * Role berfungsi sebagai identitas akun.
                 */
                'selectedPermissions' =>
                    $user
                        ->getDirectPermissions()
                        ->pluck('name')
                        ->values()
                        ->all(),

                'selectedRole' =>
                    $user
                        ->roles
                        ->first()
                        ?->name
                    ?? 'lead',
            ]
        );
    }

    /**
     * Memperbarui akun.
     */
    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $validated = $request->validate(
            $this->validationRules(
                $user,
                false
            ),
            $this->validationMessages()
        );

        $isCurrentUser = $request
            ->user()
            ->is($user);

        $wasDeveloper = $user
            ->hasRole('developer');

        $willBeDeveloper =
            $validated['role']
            === 'developer';

        $willBeActive =
            (bool) $validated[
                'is_active'
            ];

        /*
         * Developer yang sedang login tidak boleh
         * mencabut akses developer miliknya sendiri.
         */
        if (
            $isCurrentUser
            &&
            !$willBeDeveloper
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Anda tidak dapat menghapus role developer dari akun sendiri.'
                );
        }

        /*
         * Akun yang sedang dipakai tidak boleh dinonaktifkan.
         */
        if (
            $isCurrentUser
            &&
            !$willBeActive
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Anda tidak dapat menonaktifkan akun sendiri.'
                );
        }

        /*
         * Developer terakhir tidak boleh diturunkan rolenya.
         */
        if (
            $wasDeveloper
            &&
            !$willBeDeveloper
            &&
            $this->developerCount() <= 1
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Developer terakhir tidak dapat diubah menjadi role lain.'
                );
        }

        $permissions = $this
            ->sanitizePermissions(
                $validated['permissions']
                ?? []
            );

        try {
            DB::transaction(
                function () use (
                    $validated,
                    $permissions,
                    $user
                ) {
                    $updateData = [
                        'name' =>
                            trim(
                                $validated['name']
                            ),

                        'email' =>
                            mb_strtolower(
                                trim(
                                    $validated['email']
                                ),
                                'UTF-8'
                            ),

                        'is_active' =>
                            (bool) $validated[
                                'is_active'
                            ],
                    ];

                    /*
                     * Password hanya diubah apabila diisi.
                     */
                    if (
                        !empty(
                            $validated['password']
                        )
                    ) {
                        $updateData['password'] =
                            Hash::make(
                                $validated['password']
                            );
                    }

                    $user->forceFill(
                        $updateData
                    )->save();

                    $user->syncRoles([
                        $validated['role'],
                    ]);

                    $user->syncPermissions(
                        $permissions
                    );
                }
            );

            $this->clearPermissionCache();

            return redirect()
                ->route(
                    'developer.users.edit',
                    $user
                )
                ->with(
                    'success',
                    'Akun dan hak akses berhasil diperbarui.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Data gagal diperbarui: ' .
                    $e->getMessage()
                );
        }
    }

    /**
     * Mengaktifkan atau menonaktifkan akun.
     */
    public function toggleActive(
        Request $request,
        User $user
    ): RedirectResponse {
        if (
            $request->user()->is($user)
        ) {
            return back()->with(
                'error',
                'Anda tidak dapat menonaktifkan akun sendiri.'
            );
        }

        if (
            $user->hasRole('developer')
            &&
            $user->is_active
            &&
            $this->developerCount() <= 1
        ) {
            return back()->with(
                'error',
                'Developer aktif terakhir tidak dapat dinonaktifkan.'
            );
        }

        $user->forceFill([
            'is_active' =>
                !$user->is_active,
        ])->save();

        return back()->with(
            'success',
            $user->is_active
                ? 'Akun berhasil diaktifkan.'
                : 'Akun berhasil dinonaktifkan.'
        );
    }

    /**
     * Daftar aturan validasi.
     */
    private function validationRules(
        ?User $user,
        bool $creating
    ): array {
        $roleNames = $this
            ->availableRoles()
            ->pluck('name')
            ->all();

        $permissionNames =
            $this->configuredPermissionNames();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $user?->id
                ),
            ],

            'role' => [
                'required',
                Rule::in(
                    $roleNames
                ),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'password' => [
                $creating
                    ? 'required'
                    : 'nullable',

                'string',
                'min:8',
                'confirmed',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'string',
                Rule::in(
                    $permissionNames
                ),
            ],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'name.required' =>
                'Nama wajib diisi.',

            'email.required' =>
                'Email wajib diisi.',

            'email.email' =>
                'Format email tidak valid.',

            'email.unique' =>
                'Email sudah digunakan akun lain.',

            'role.required' =>
                'Role wajib dipilih.',

            'password.required' =>
                'Password wajib diisi.',

            'password.min' =>
                'Password minimal 8 karakter.',

            'password.confirmed' =>
                'Konfirmasi password tidak sesuai.',
        ];
    }

    private function availableRoles()
    {
        return Role::query()
            ->where(
                'guard_name',
                'web'
            )
            ->orderByRaw(
                "FIELD(name, 'developer', 'spv', 'lead')"
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * Permission yang tercantum di config/access.php.
     */
    private function configuredPermissionNames(): array
    {
        return collect(
            config(
                'access.modules',
                []
            )
        )
            ->flatMap(
                fn (array $module) =>
                    array_keys(
                        $module[
                            'permissions'
                        ]
                        ?? []
                    )
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Mencegah permission asing disimpan.
     */
    private function sanitizePermissions(
        array $permissions
    ): array {
        $allowed =
            $this->configuredPermissionNames();

        return collect($permissions)
            ->map(
                fn ($permission) =>
                    trim(
                        (string) $permission
                    )
            )
            ->filter()
            ->intersect($allowed)
            ->unique()
            ->values()
            ->all();
    }

    private function developerCount(): int
    {
        return User::query()
            ->role('developer')
            ->where(
                'is_active',
                true
            )
            ->count();
    }

    private function clearPermissionCache(): void
    {
        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}