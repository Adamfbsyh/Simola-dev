<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Riwayat upload dan analisis spreadsheet
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'fleet_import_batches',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->uuid('uuid')
                    ->unique();

                $table->string(
                    'original_name'
                );

                $table->string(
                    'stored_path'
                );

                $table
                    ->string(
                        'file_hash',
                        64
                    )
                    ->index();

                $table
                    ->string(
                        'status',
                        30
                    )
                    ->default('analyzed')
                    ->index();

                $table
                    ->longText(
                        'analysis_json'
                    )
                    ->nullable();

                $table
                    ->foreignId(
                        'uploaded_by'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'imported_by'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp(
                        'imported_at'
                    )
                    ->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Periode atau versi grouping
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'fleet_grouping_periods',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'import_batch_id'
                    )
                    ->nullable()
                    ->constrained(
                        'fleet_import_batches'
                    )
                    ->nullOnDelete();

                $table->string(
                    'name'
                );

                $table
                    ->date(
                        'effective_date'
                    )
                    ->nullable();

                $table
                    ->string(
                        'status',
                        30
                    )
                    ->default('draft')
                    ->index();

                $table
                    ->string(
                        'source_file_name'
                    )
                    ->nullable();

                $table
                    ->foreignId(
                        'created_by'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'published_by'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp(
                        'published_at'
                    )
                    ->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Hasil grouping setiap kendaraan
        |--------------------------------------------------------------------------
        |
        | pc_initial:
        | PC awal dari SETTING ROTASI.
        |
        | pc_target:
        | PC akhir dari SETTING ROTASI.
        |
        | pc_final:
        | Hasil akhir dari PC SET UTAMA.
        |
        */

        Schema::create(
            'fleet_grouping_assignments',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'grouping_period_id'
                    )
                    ->constrained(
                        'fleet_grouping_periods'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'vehicle_id'
                    )
                    ->constrained(
                        'fleet_vehicles'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'company_id'
                    )
                    ->nullable()
                    ->constrained(
                        'fleet_companies'
                    )
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'terminal_id'
                    )
                    ->nullable()
                    ->constrained(
                        'fleet_terminals'
                    )
                    ->nullOnDelete();

                $table
                    ->unsignedTinyInteger(
                        'pc_initial'
                    )
                    ->nullable();

                $table
                    ->unsignedTinyInteger(
                        'pc_target'
                    )
                    ->nullable();

                $table
                    ->unsignedTinyInteger(
                        'pc_final'
                    )
                    ->nullable();

                /*
                 * Snapshot menjaga histori saat master diedit.
                 */

                $table->string(
                    'plate_number_snapshot',
                    30
                );

                $table->string(
                    'company_name_snapshot'
                )->nullable();

                $table->string(
                    'terminal_name_snapshot'
                )->nullable();

                $table
                    ->unsignedInteger(
                        'source_rotation_row'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'source_final_row'
                    )
                    ->nullable();

                $table
                    ->string(
                        'validation_status',
                        40
                    )
                    ->default('pending')
                    ->index();

                $table->text(
                    'validation_notes'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'grouping_period_id',
                        'vehicle_id',
                    ],
                    'fleet_grouping_period_vehicle_unique'
                );

                $table->index(
                    [
                        'grouping_period_id',
                        'pc_final',
                    ],
                    'fleet_grouping_period_pc_final_index'
                );

                $table->index(
                    [
                        'grouping_period_id',
                        'terminal_id',
                    ],
                    'fleet_grouping_period_terminal_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'fleet_grouping_assignments'
        );

        Schema::dropIfExists(
            'fleet_grouping_periods'
        );

        Schema::dropIfExists(
            'fleet_import_batches'
        );
    }
};