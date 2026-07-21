<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class SetDeveloperUser extends Command
{
    protected $signature =
        'access:set-developer
        {email : Email akun yang akan dijadikan developer}';

    protected $description =
        'Menetapkan akun sebagai developer SIMOLA';

    public function handle(): int
    {
        $email = mb_strtolower(
            trim(
                (string) $this->argument('email')
            ),
            'UTF-8'
        );

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $this->error(
                'Format email tidak valid.'
            );

            return self::FAILURE;
        }

        $user = User::query()
            ->whereRaw(
                'LOWER(email) = ?',
                [
                    $email,
                ]
            )
            ->first();

        if (!$user) {
            $this->error(
                'Akun dengan email "' .
                $email .
                '" tidak ditemukan.'
            );

            return self::FAILURE;
        }

        $developerRole = Role::query()
            ->where('name', 'developer')
            ->where('guard_name', 'web')
            ->first();

        if (!$developerRole) {
            $this->error(
                'Role developer belum tersedia. ' .
                'Jalankan AccessControlSeeder terlebih dahulu.'
            );

            return self::FAILURE;
        }

        try {
            DB::transaction(
                function () use (
                    $user,
                    $developerRole
                ) {
                    /*
                     * Developer hanya memiliki role developer.
                     */
                    $user->syncRoles([
                        $developerRole,
                    ]);

                    $user->forceFill([
                        'is_active' => true,
                    ])->save();
                }
            );

            app(
                PermissionRegistrar::class
            )->forgetCachedPermissions();

            $this->newLine();

            $this->info(
                'Akun berhasil dijadikan developer.'
            );

            $this->table(
                [
                    'Informasi',
                    'Nilai',
                ],
                [
                    [
                        'ID',
                        $user->id,
                    ],
                    [
                        'Nama',
                        $user->name,
                    ],
                    [
                        'Email',
                        $user->email,
                    ],
                    [
                        'Role',
                        'developer',
                    ],
                    [
                        'Status',
                        'aktif',
                    ],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error(
                'Gagal menetapkan developer: ' .
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}