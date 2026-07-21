<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Pastikan source_block tidak kosong
        |--------------------------------------------------------------------------
        */

        DB::table('driver_daily_assignments')
            ->whereNull('source_block')
            ->update([
                'source_block' => 'history',
            ]);

        DB::statement(
            'ALTER TABLE driver_daily_assignments
             MODIFY source_block VARCHAR(20) NOT NULL'
        );

        /*
        |--------------------------------------------------------------------------
        | Ganti unique key lama
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'driver_daily_assignments',
            function (Blueprint $table) {
                $table->dropUnique(
                    'driver_assignment_source_unique'
                );

                $table->unique(
                    [
                        'source_date',
                        'source_row',
                        'source_block',
                    ],
                    'driver_assignment_source_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'driver_daily_assignments',
            function (Blueprint $table) {
                $table->dropUnique(
                    'driver_assignment_source_unique'
                );

                $table->unique(
                    [
                        'source_date',
                        'source_row',
                    ],
                    'driver_assignment_source_unique'
                );
            }
        );

        DB::statement(
            'ALTER TABLE driver_daily_assignments
             MODIFY source_block VARCHAR(20) NULL'
        );
    }
};