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
        Schema::create('report_uploads', function (Blueprint $table) {
            $table->id();

            $table->string('jenis_laporan'); 
            // pelanggaran, kendala, accident, errorlog

            $table->string('periode')->default('Harian');
            // Harian, Mingguan, Bulanan, Tahunan

            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->integer('bulan')->nullable();
            $table->integer('tahun')->nullable();

            $table->string('nama_file');
            $table->string('path_file')->nullable();
            $table->string('file_hash')->nullable();

            $table->integer('total_data')->default(0);
            $table->string('status')->default('Berhasil');
            $table->text('catatan')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_uploads');
    }
};
