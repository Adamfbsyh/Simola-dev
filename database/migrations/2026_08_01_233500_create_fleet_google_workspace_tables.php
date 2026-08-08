<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'fleet_google_accounts',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->unique()
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table
                    ->string('google_email')
                    ->nullable();

                /*
                 * Disimpan terenkripsi melalui cast model.
                 * Payload berisi access_token, refresh_token,
                 * expires_in, created, dan scope.
                 */
                $table->longText('token_payload');

                $table->json('scopes')->nullable();

                $table
                    ->timestamp('connected_at')
                    ->nullable();

                $table
                    ->timestamp('last_refreshed_at')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'fleet_google_sync_logs',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('sync_type', 40)
                    ->index();

                $table
                    ->string('status', 20)
                    ->index();

                $table
                    ->foreignId('grouping_period_id')
                    ->nullable()
                    ->constrained('fleet_grouping_periods')
                    ->nullOnDelete();

                $table
                    ->date('target_date')
                    ->nullable()
                    ->index();

                $table
                    ->unsignedInteger('total_items')
                    ->default(0);

                $table
                    ->unsignedInteger('created_items')
                    ->default(0);

                $table
                    ->unsignedInteger('updated_items')
                    ->default(0);

                $table
                    ->unsignedInteger('skipped_items')
                    ->default(0);

                $table
                    ->unsignedInteger('failed_items')
                    ->default(0);

                $table->text('message')->nullable();
                $table->json('metadata')->nullable();

                $table
                    ->timestamp('started_at')
                    ->nullable();

                $table
                    ->timestamp('finished_at')
                    ->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            }
        );

        Schema::create(
            'fleet_google_evidence_folders',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('grouping_period_id')
                    ->constrained('fleet_grouping_periods')
                    ->cascadeOnDelete();

                $table
                    ->date('workspace_date')
                    ->index();

                $table
                    ->unsignedTinyInteger('pc_number')
                    ->index();

                $table
                    ->foreignId('vehicle_id')
                    ->nullable()
                    ->constrained('fleet_vehicles')
                    ->nullOnDelete();

                $table->string('plate_number_snapshot');
                $table->string(
                    'normalized_plate_number_snapshot'
                );

                $table->string('month_folder_id');
                $table->string('date_folder_id');
                $table->string('pc_folder_id');
                $table->string('vehicle_folder_id');

                $table->string('pelanggaran_folder_id');
                $table->string('errorlog_folder_id');
                $table->string('accident_folder_id');
                $table->string('insiden_folder_id');

                $table->timestamps();

                $table->unique(
                    [
                        'workspace_date',
                        'grouping_period_id',
                        'pc_number',
                        'normalized_plate_number_snapshot',
                    ],
                    'fleet_google_evidence_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'fleet_google_evidence_folders'
        );

        Schema::dropIfExists(
            'fleet_google_sync_logs'
        );

        Schema::dropIfExists(
            'fleet_google_accounts'
        );
    }
};
