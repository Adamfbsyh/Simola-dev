<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Master TLPG / Terminal / Depo
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'fleet_terminals',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('code', 50)
                    ->nullable()
                    ->unique();

                $table->string('name');

                $table
                    ->string('normalized_name')
                    ->unique();

                $table
                    ->decimal('latitude', 10, 7)
                    ->nullable();

                $table
                    ->decimal('longitude', 10, 7)
                    ->nullable();

                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->text('notes')->nullable();

                $table->timestamps();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Master Perusahaan / SPBE
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'fleet_companies',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('code', 50)
                    ->nullable()
                    ->unique();

                $table->string('name');

                $table
                    ->string('normalized_name')
                    ->unique();

                $table
                    ->foreignId('default_terminal_id')
                    ->nullable()
                    ->constrained('fleet_terminals')
                    ->nullOnDelete();

                $table
                    ->decimal('latitude', 10, 7)
                    ->nullable();

                $table
                    ->decimal('longitude', 10, 7)
                    ->nullable();

                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->text('notes')->nullable();

                $table->timestamps();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Profil jarak SPBE menuju TLPG
        |--------------------------------------------------------------------------
        |
        | Data distance_km dapat berasal dari hasil Maps yang sudah divalidasi.
        | Latitude dan longitude tetap disimpan sebagai data pendukung.
        |
        */

        Schema::create(
            'fleet_distance_profiles',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('company_id')
                    ->constrained('fleet_companies')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('terminal_id')
                    ->constrained('fleet_terminals')
                    ->cascadeOnDelete();

                $table
                    ->decimal('distance_km', 10, 2)
                    ->nullable();

                $table
                    ->string('distance_category', 30)
                    ->nullable()
                    ->index();

                $table
                    ->unsignedSmallInteger('weight')
                    ->nullable();

                $table
                    ->string('distance_source', 50)
                    ->default('manual');

                $table
                    ->timestamp('last_verified_at')
                    ->nullable();

                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->text('route_notes')->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'company_id',
                        'terminal_id',
                    ],
                    'fleet_distance_company_terminal_unique'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Master kendaraan
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'fleet_vehicles',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('plate_number', 30);

                $table
                    ->string(
                        'normalized_plate_number',
                        30
                    )
                    ->unique();

                $table
                    ->foreignId('company_id')
                    ->nullable()
                    ->constrained('fleet_companies')
                    ->nullOnDelete();

                $table
                    ->string('unit_code', 100)
                    ->nullable();

                $table
                    ->date('effective_from')
                    ->nullable();

                $table
                    ->date('effective_until')
                    ->nullable();

                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'company_id',
                        'is_active',
                    ]
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Riwayat perubahan nopol
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'fleet_vehicle_plate_histories',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('vehicle_id')
                    ->constrained('fleet_vehicles')
                    ->cascadeOnDelete();

                $table
                    ->string('old_plate_number', 30);

                $table
                    ->string('new_plate_number', 30);

                $table
                    ->string(
                        'old_normalized_plate_number',
                        30
                    );

                $table
                    ->string(
                        'new_normalized_plate_number',
                        30
                    );

                $table
                    ->date('effective_date');

                $table->text('reason')->nullable();

                $table
                    ->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'vehicle_id',
                        'effective_date',
                    ],
                    'fleet_plate_history_vehicle_date_index'
                );

                $table->index(
                    'old_normalized_plate_number',
                    'fleet_plate_history_old_plate_index'
                );

                $table->index(
                    'new_normalized_plate_number',
                    'fleet_plate_history_new_plate_index'
                );
            }
        );
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'fleet_vehicle_plate_histories'
        );

        Schema::dropIfExists(
            'fleet_vehicles'
        );

        Schema::dropIfExists(
            'fleet_distance_profiles'
        );

        Schema::dropIfExists(
            'fleet_companies'
        );

        Schema::dropIfExists(
            'fleet_terminals'
        );
    }
};