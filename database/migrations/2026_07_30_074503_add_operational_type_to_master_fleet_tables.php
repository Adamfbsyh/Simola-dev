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
        | Master Kendaraan
        |--------------------------------------------------------------------------
        */

        $vehicleHasOperationalType = Schema::hasColumn(
            'fleet_vehicles',
            'operational_type'
        );

        $vehicleHasOperatorName = Schema::hasColumn(
            'fleet_vehicles',
            'operator_name'
        );

        Schema::table(
            'fleet_vehicles',
            function (Blueprint $table) use (
                $vehicleHasOperationalType,
                $vehicleHasOperatorName
            ): void {
                if (!$vehicleHasOperationalType) {
                    $table
                        ->string('operational_type', 2)
                        ->default('P2')
                        ->after('company_id')
                        ->index();
                }

                if (!$vehicleHasOperatorName) {
                    $table
                        ->string('operator_name', 255)
                        ->nullable()
                        ->after('operational_type');
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Snapshot pada Periode Grouping
        |--------------------------------------------------------------------------
        */

        $assignmentHasOperationalType = Schema::hasColumn(
            'fleet_grouping_assignments',
            'operational_type'
        );

        $assignmentHasOperatorName = Schema::hasColumn(
            'fleet_grouping_assignments',
            'operator_name_snapshot'
        );

        Schema::table(
            'fleet_grouping_assignments',
            function (Blueprint $table) use (
                $assignmentHasOperationalType,
                $assignmentHasOperatorName
            ): void {
                if (!$assignmentHasOperationalType) {
                    $table
                        ->string('operational_type', 2)
                        ->default('P2')
                        ->after('terminal_id')
                        ->index();
                }

                if (!$assignmentHasOperatorName) {
                    $table
                        ->string('operator_name_snapshot', 255)
                        ->nullable()
                        ->after('company_name_snapshot');
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Data lama dianggap P2 terlebih dahulu
        |--------------------------------------------------------------------------
        |
        | Kendaraan P1 akan ditandai melalui Master Kendaraan.
        | Kita tidak menebak otomatis berdasarkan nama perusahaan agar tidak
        | terjadi salah klasifikasi.
        |
        */

        DB::table('fleet_vehicles')
            ->whereNull('operational_type')
            ->update([
                'operational_type' => 'P2',
            ]);

        DB::table('fleet_grouping_assignments')
            ->whereNull('operational_type')
            ->update([
                'operational_type' => 'P2',
            ]);
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'fleet_grouping_assignments',
                'operator_name_snapshot'
            )
        ) {
            Schema::table(
                'fleet_grouping_assignments',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'operator_name_snapshot'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'fleet_grouping_assignments',
                'operational_type'
            )
        ) {
            Schema::table(
                'fleet_grouping_assignments',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'operational_type'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'fleet_vehicles',
                'operator_name'
            )
        ) {
            Schema::table(
                'fleet_vehicles',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'operator_name'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'fleet_vehicles',
                'operational_type'
            )
        ) {
            Schema::table(
                'fleet_vehicles',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'operational_type'
                    );
                }
            );
        }
    }
};