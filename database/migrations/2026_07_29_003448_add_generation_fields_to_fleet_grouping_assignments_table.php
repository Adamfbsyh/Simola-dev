<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'fleet_grouping_assignments',
            function (Blueprint $table): void {
                $table
                    ->decimal('distance_km', 10, 2)
                    ->nullable()
                    ->after('terminal_id');

                $table
                    ->string('distance_category', 30)
                    ->nullable()
                    ->after('distance_km');

                $table
                    ->unsignedTinyInteger('distance_weight')
                    ->nullable()
                    ->after('distance_category');

                $table
                    ->string('assignment_source', 30)
                    ->default('imported')
                    ->after('validation_notes');

                $table
                    ->timestamp('generated_at')
                    ->nullable()
                    ->after('assignment_source');

                $table
                    ->foreignId('manually_adjusted_by')
                    ->nullable()
                    ->after('generated_at')
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('manually_adjusted_at')
                    ->nullable()
                    ->after('manually_adjusted_by');

                $table
                    ->text('manual_adjustment_note')
                    ->nullable()
                    ->after('manually_adjusted_at');

                $table->index(
                    [
                        'grouping_period_id',
                        'assignment_source',
                    ],
                    'fleet_grouping_source_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'fleet_grouping_assignments',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'fleet_grouping_source_index'
                );

                $table->dropForeign([
                    'manually_adjusted_by',
                ]);

                $table->dropColumn([
                    'distance_km',
                    'distance_category',
                    'distance_weight',
                    'assignment_source',
                    'generated_at',
                    'manually_adjusted_by',
                    'manually_adjusted_at',
                    'manual_adjustment_note',
                ]);
            }
        );
    }
};