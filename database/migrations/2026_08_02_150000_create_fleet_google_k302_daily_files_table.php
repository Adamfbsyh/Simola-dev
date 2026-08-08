<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable(
                'fleet_google_k302_daily_files'
            )
        ) {
            return;
        }

        Schema::create(
            'fleet_google_k302_daily_files',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('grouping_period_id')
                    ->nullable()
                    ->constrained('fleet_grouping_periods')
                    ->nullOnDelete();

                $table
                    ->date('workspace_date')
                    ->unique();

                $table->string('month_folder_id');
                $table->string('date_folder_id');
                $table->string('template_spreadsheet_id');

                $table
                    ->string('spreadsheet_id')
                    ->unique();

                $table->string('spreadsheet_name');
                $table->text('spreadsheet_url');

                $table
                    ->string('status', 20)
                    ->default('active')
                    ->index();

                $table
                    ->timestamp('last_synced_at')
                    ->nullable();

                $table->json('metadata')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'fleet_google_k302_daily_files'
        );
    }
};
