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
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable();
            $table->string('periode')->nullable();
            $table->string('unit')->nullable();
            $table->string('kategori')->nullable();
            $table->integer('target')->default(0);
            $table->integer('realisasi')->default(0);
            $table->string('status')->nullable();
            $table->string('kendala')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
