<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('fleet_google_accounts')
        ) {
            return;
        }

        if (
            !Schema::hasColumn(
                'fleet_google_accounts',
                'purpose'
            )
        ) {
            Schema::table(
                'fleet_google_accounts',
                function (Blueprint $table): void {
                    $table
                        ->string('purpose', 30)
                        ->default('k302')
                        ->after('user_id')
                        ->index();
                }
            );
        }

        DB::table('fleet_google_accounts')
            ->whereNull('purpose')
            ->orWhere('purpose', '')
            ->update([
                'purpose' => 'k302',
            ]);

        /*
         * Migration awal membuat user_id unik. Sekarang satu pengguna SIMOLA
         * boleh menyimpan dua koneksi Google: K3-02 dan Evidence.
         */
        /*
         * Tambahkan index gabungan lebih dahulu agar foreign key user_id
         * tetap mempunyai index saat unique lama dilepas.
         */
        Schema::table(
            'fleet_google_accounts',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'user_id',
                        'purpose',
                    ],
                    'fleet_google_accounts_user_purpose_unique'
                );
            }
        );

        Schema::table(
            'fleet_google_accounts',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'fleet_google_accounts_user_id_unique'
                );
            }
        );
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('fleet_google_accounts')
            || !Schema::hasColumn(
                'fleet_google_accounts',
                'purpose'
            )
        ) {
            return;
        }

        /*
         * Rollback ke desain satu akun: pertahankan koneksi K3-02 saja.
         */
        DB::table('fleet_google_accounts')
            ->where('purpose', '!=', 'k302')
            ->delete();

        Schema::table(
            'fleet_google_accounts',
            function (Blueprint $table): void {
                $table->unique('user_id');
            }
        );

        Schema::table(
            'fleet_google_accounts',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'fleet_google_accounts_user_purpose_unique'
                );

                $table->dropIndex(
                    'fleet_google_accounts_purpose_index'
                );

                $table->dropColumn('purpose');
            }
        );
    }
};
