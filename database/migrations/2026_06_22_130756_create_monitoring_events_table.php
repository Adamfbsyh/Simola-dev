<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoring_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('report_upload_id')
                ->nullable()
                ->constrained('report_uploads')
                ->cascadeOnDelete();

            $table->string('event_type');
            // pelanggaran, kendala, accident, errorlog

            $table->date('event_date')->nullable();
            $table->string('event_time')->nullable();

            $table->string('nopol')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('tlpg')->nullable();

            $table->string('event_name')->nullable();
            // contoh: Over Speed, Perbaikan Ban, Accident Aktif, Trackvision Offline

            $table->string('category')->nullable();
            // contoh: SP 1, SP 2, PASIF, AKTIF, Near Miss, Error System

            $table->string('severity')->nullable();
            // rendah, sedang, tinggi, kritis

            $table->integer('score_impact')->default(0);
            // nilai pengurang skor pengemudi

            $table->integer('source_page')->nullable();
            $table->integer('source_row')->nullable();

            $table->text('evidence_link')->nullable();
            $table->text('description')->nullable();

            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index('event_type');
            $table->index('event_date');
            $table->index('nopol');
            $table->index('tlpg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_events');
    }
};
