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
        Schema::create('laporan_files', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->string('path_file')->nullable();
            $table->string('jenis_laporan')->default('K3-02.2');
            $table->date('tanggal_laporan')->nullable();
            $table->string('periode')->default('Harian');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_files');
    }
};
