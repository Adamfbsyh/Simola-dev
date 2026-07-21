<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('k32_daily_records', function (Blueprint $table) {
            $table->id();

            $table->date('source_date')->index();
            $table->string('nopol', 30)->index();
            $table->string('tlpg', 120)->nullable()->index();
            $table->string('event_name', 180)->index();

            $table->unsignedInteger('spreadsheet_count')
                ->default(0);

            $table->unsignedBigInteger('source_row')
                ->nullable();

            $table->string('source_sheet', 100)
                ->default('K3-2.2 DAILY');

            $table->timestamp('synced_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'source_date',
                    'nopol',
                    'event_name',
                ],
                'k32_daily_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('k32_daily_records');
    }
};