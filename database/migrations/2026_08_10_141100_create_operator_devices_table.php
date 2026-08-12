<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('operator_devices')) {
            Schema::create('operator_devices', function (Blueprint $table): void {
                $table->id();
                $table->string('fleet_type', 40);
                $table->unsignedSmallInteger('pc_number');
                $table->string('label', 100)->nullable();
                $table->string('device_token_hash', 64)->nullable()->unique();
                $table->string('activation_code', 6)->nullable()->index();
                $table->timestamp('activation_expires_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->timestamp('released_at')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->timestamps();

                $table->unique(
                    ['fleet_type', 'pc_number'],
                    'operator_devices_fleet_pc_unique'
                );
            });
        }

        /*
         * Migrasikan identitas PC lama (jika ada) menjadi daftar perangkat.
         * Tidak mengaktifkan perangkat; pengawas tetap harus membuat kode aktivasi.
         */
        if (Schema::hasTable('operator_pc_assignments')) {
            $now = now();

            DB::table('operator_pc_assignments')
                ->select('fleet_type', 'pc_number')
                ->where('is_active', true)
                ->distinct()
                ->orderBy('fleet_type')
                ->orderBy('pc_number')
                ->get()
                ->each(function ($row) use ($now): void {
                    DB::table('operator_devices')->updateOrInsert(
                        [
                            'fleet_type' => $row->fleet_type,
                            'pc_number' => $row->pc_number,
                        ],
                        [
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_devices');
    }
};
