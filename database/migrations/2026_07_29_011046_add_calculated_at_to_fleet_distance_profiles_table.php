<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'fleet_distance_profiles',
            function (Blueprint $table): void {
                if (
                    !Schema::hasColumn(
                        'fleet_distance_profiles',
                        'calculated_at'
                    )
                ) {
                    $table
                        ->timestamp('calculated_at')
                        ->nullable()
                        ->after('distance_source');
                }
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'fleet_distance_profiles',
            function (Blueprint $table): void {
                if (
                    Schema::hasColumn(
                        'fleet_distance_profiles',
                        'calculated_at'
                    )
                ) {
                    $table->dropColumn(
                        'calculated_at'
                    );
                }
            }
        );
    }
};