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
            Schema::hasTable('fleet_companies')
            &&
            !Schema::hasColumn(
                'fleet_companies',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_companies',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'fleet_type',
                            32
                        )
                        ->default(
                            FleetType::LPG
                        )
                        ->index();
                }
            );
        }

        if (
            Schema::hasTable('fleet_distance_profiles')
            &&
            !Schema::hasColumn(
                'fleet_distance_profiles',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_distance_profiles',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'fleet_type',
                            32
                        )
                        ->default(
                            FleetType::LPG
                        )
                        ->index();
                }
            );
        }

        if (
            Schema::hasTable('fleet_companies')
            &&
            Schema::hasColumn(
                'fleet_companies',
                'fleet_type'
            )
        ) {
            DB::table('fleet_companies')
                ->where(
                    function ($query): void {
                        $query
                            ->whereNull('fleet_type')
                            ->orWhere(
                                'fleet_type',
                                ''
                            );
                    }
                )
                ->update([
                    'fleet_type' =>
                        FleetType::LPG,
                ]);
        }

        if (
            Schema::hasTable('fleet_distance_profiles')
            &&
            Schema::hasColumn(
                'fleet_distance_profiles',
                'fleet_type'
            )
        ) {
            DB::table('fleet_distance_profiles')
                ->where(
                    function ($query): void {
                        $query
                            ->whereNull('fleet_type')
                            ->orWhere(
                                'fleet_type',
                                ''
                            );
                    }
                )
                ->update([
                    'fleet_type' =>
                        FleetType::LPG,
                ]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('fleet_distance_profiles')
            &&
            Schema::hasColumn(
                'fleet_distance_profiles',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_distance_profiles',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'fleet_type'
                    );
                }
            );
        }

        if (
            Schema::hasTable('fleet_companies')
            &&
            Schema::hasColumn(
                'fleet_companies',
                'fleet_type'
            )
        ) {
            Schema::table(
                'fleet_companies',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'fleet_type'
                    );
                }
            );
        }
    }
};
