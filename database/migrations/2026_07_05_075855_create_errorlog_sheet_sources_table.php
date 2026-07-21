<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('errorlog_sheet_sources', function (Blueprint $table) {
            $table->id();

            $table->string('spreadsheet_id', 180);
            $table->text('spreadsheet_url');
            $table->string('sheet_name', 150)
                ->default('Error Log System');

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('last_synced_at')
                ->nullable();

            $table->unsignedInteger('total_rows')
                ->default(0);

            $table->unsignedInteger('created_rows')
                ->default(0);

            $table->unsignedInteger('updated_rows')
                ->default(0);

            $table->string('status', 30)
                ->default('belum_sinkron');

            $table->text('last_error')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'spreadsheet_id',
                    'sheet_name',
                    'year',
                    'month',
                ],
                'errorlog_sheet_source_unique'
            );
        });

        Schema::table('monitoring_events', function (Blueprint $table) {
            $table->foreignId('errorlog_source_id')
                ->nullable()
                ->after('report_upload_id')
                ->constrained('errorlog_sheet_sources')
                ->cascadeOnDelete();

            $table->string('source_key', 64)
                ->nullable()
                ->after('source_row');

            $table->unique(
                [
                    'errorlog_source_id',
                    'source_key',
                ],
                'monitoring_errorlog_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_events', function (Blueprint $table) {
            $table->dropUnique(
                'monitoring_errorlog_source_unique'
            );

            $table->dropForeign([
                'errorlog_source_id',
            ]);

            $table->dropColumn([
                'errorlog_source_id',
                'source_key',
            ]);
        });

        Schema::dropIfExists('errorlog_sheet_sources');
    }
};