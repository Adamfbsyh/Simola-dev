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
         * K3-06.1 Daily tidak memiliki NOPOL.
         * Kolom ini dibuat nullable agar data aktivitas AMT
         * tetap dapat disimpan.
         */
        DB::statement(
            'ALTER TABLE driver_daily_assignments
             MODIFY nopol VARCHAR(50) NULL'
        );

        Schema::table(
            'driver_daily_assignments',
            function (Blueprint $table) {
                $table->decimal(
                    'total_distance',
                    12,
                    2
                )
                    ->nullable()
                    ->after('driver_name');

                $table->unsignedInteger(
                    'travel_seconds'
                )
                    ->nullable()
                    ->after('total_distance');

                $table->unsignedInteger(
                    'stop_seconds'
                )
                    ->nullable()
                    ->after('travel_seconds');

                $table->string(
                    'source_block',
                    20
                )
                    ->nullable()
                    ->after('source_row');

                $table->index(
                    [
                        'source_date',
                        'driver_name',
                    ],
                    'driver_assignment_date_name_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'driver_daily_assignments',
            function (Blueprint $table) {
                $table->dropIndex(
                    'driver_assignment_date_name_index'
                );

                $table->dropColumn([
                    'total_distance',
                    'travel_seconds',
                    'stop_seconds',
                    'source_block',
                ]);
            }
        );

        DB::statement(
            'ALTER TABLE driver_daily_assignments
             MODIFY nopol VARCHAR(50) NOT NULL'
        );
    }
};