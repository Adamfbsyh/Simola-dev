<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $guardName = 'web';

        /*
        |--------------------------------------------------------------------------
        | Role
        |--------------------------------------------------------------------------
        */

        foreach (
            config('access.roles', [])
            as $roleName => $roleLabel
        ) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Permission
        |--------------------------------------------------------------------------
        */

        foreach (
            config('access.modules', [])
            as $module
        ) {
            foreach (
                $module['permissions'] ?? []
                as $permissionName => $permissionLabel
            ) {
                Permission::query()->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guardName,
                ]);
            }
        }

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}