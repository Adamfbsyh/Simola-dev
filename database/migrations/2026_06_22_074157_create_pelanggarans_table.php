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
        Schema::create('pelanggarans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laporan_file_id')
                ->constrained('laporan_files')
                ->cascadeOnDelete();

            $table->date('tanggal_laporan')->nullable();
            $table->integer('no_urut')->nullable();

            $table->string('nopol')->nullable();
            $table->string('terminal')->nullable();
            $table->string('driver')->nullable();

            $table->string('kategori_sanksi')->nullable();
            $table->text('jenis_pelanggaran')->nullable();

            $table->integer('nilai')->default(1);
            $table->string('evidence')->nullable();
            $table->integer('row_excel')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggarans');
    }
};
