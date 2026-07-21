<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'driver_daily_assignments',
            function (Blueprint $table) {
                $table->id();

                /*
                 * Tanggal penugasan AMT sesuai K3-06.1 Daily.
                 */
                $table->date('source_date');

                /*
                 * NOPOL kendaraan.
                 */
                $table->string('nopol', 50);

                /*
                 * Nama AMT/pengemudi.
                 */
                $table->string('driver_name', 180)
                    ->nullable();

                /*
                 * Terminal/TLPG.
                 */
                $table->string('tlpg', 150)
                    ->nullable();

                /*
                 * Nomor baris asal pada Google Spreadsheet.
                 */
                $table->unsignedInteger('source_row');

                /*
                 * Data mentah dari spreadsheet untuk audit.
                 */
                $table->json('raw_data')
                    ->nullable();

                /*
                 * Waktu terakhir data disinkronkan.
                 */
                $table->timestamp('synced_at')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    'source_date',
                    'driver_assignment_date_index'
                );

                $table->index(
                    'nopol',
                    'driver_assignment_nopol_index'
                );

                $table->index(
                    'driver_name',
                    'driver_assignment_name_index'
                );

                $table->index(
                    'tlpg',
                    'driver_assignment_tlpg_index'
                );

                /*
                 * Satu baris spreadsheet hanya disimpan sekali.
                 */
                $table->unique(
                    [
                        'source_date',
                        'source_row',
                    ],
                    'driver_assignment_source_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'driver_daily_assignments'
        );
    }
};