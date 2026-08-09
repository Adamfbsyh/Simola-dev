<?php

use App\Support\MasterFleet\FleetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasColumn(
                'fleet_vehicles',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_vehicles',
                function (
                    Blueprint $table
                ): void {
                    $table
                        ->string(
                            'fleet_type',
                            32
                        )
                        ->default(
                            FleetType::LPG
                        )
                        ->index()
                        ->after(
                            'normalized_plate_number'
                        );
                }
            );
        }

        if (
            !Schema::hasColumn(
                'fleet_grouping_periods',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_grouping_periods',
                function (
                    Blueprint $table
                ): void {
                    $table
                        ->string(
                            'fleet_type',
                            32
                        )
                        ->default(
                            FleetType::LPG
                        )
                        ->index()
                        ->after(
                            'id'
                        );
                }
            );
        }

        DB::table(
            'fleet_vehicles'
        )
            ->whereNull(
                'fleet_type'
            )
            ->orWhere(
                'fleet_type',
                ''
            )
            ->update([
                'fleet_type' =>
                    FleetType::LPG,
            ]);

        DB::table(
            'fleet_grouping_periods'
        )
            ->whereNull(
                'fleet_type'
            )
            ->orWhere(
                'fleet_type',
                ''
            )
            ->update([
                'fleet_type' =>
                    FleetType::LPG,
            ]);
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'fleet_grouping_periods',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_grouping_periods',
                function (
                    Blueprint $table
                ): void {
                    $table->dropColumn(
                        'fleet_type'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'fleet_vehicles',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_vehicles',
                function (
                    Blueprint $table
                ): void {
                    $table->dropColumn(
                        'fleet_type'
                    );
                }
            );
        }
    }
};
