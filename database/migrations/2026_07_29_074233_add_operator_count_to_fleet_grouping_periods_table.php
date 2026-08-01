<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'fleet_grouping_periods',
            function (Blueprint $table): void {
                if (
                    !Schema::hasColumn(
                        'fleet_grouping_periods',
                        'operator_count'
                    )
                ) {
                    $table
                        ->unsignedSmallInteger('operator_count')
                        ->default(12)
                        ->after('status');
                }
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'fleet_grouping_periods',
            function (Blueprint $table): void {
                if (
                    Schema::hasColumn(
                        'fleet_grouping_periods',
                        'operator_count'
                    )
                ) {
                    $table->dropColumn('operator_count');
                }
            }
        );
    }
};