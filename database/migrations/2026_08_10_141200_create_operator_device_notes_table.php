<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('operator_device_notes')) {
            return;
        }

        Schema::create('operator_device_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')
                ->constrained('operator_devices')
                ->cascadeOnDelete();
            $table->text('body');
            $table->unsignedBigInteger('source_note_id')->nullable()->index();
            $table->foreignId('source_device_id')
                ->nullable()
                ->constrained('operator_devices')
                ->nullOnDelete();
            $table->unsignedBigInteger('delivered_from_transfer_id')
                ->nullable()
                ->index();
            $table->timestamps();

            $table->index(
                ['device_id', 'updated_at'],
                'operator_device_notes_device_time_idx'
            );
        });

        /*
         * Salin sticky note legacy ke identitas PC berdasarkan assignment lama.
         * Catatan lama tidak dihapus.
         */
        if (
            Schema::hasTable('operator_notes')
            && Schema::hasTable('operator_pc_assignments')
        ) {
            $rows = DB::table('operator_notes as n')
                ->join(
                    'operator_pc_assignments as a',
                    'a.user_id',
                    '=',
                    'n.user_id'
                )
                ->join(
                    'operator_devices as d',
                    function ($join): void {
                        $join
                            ->on(
                                'd.fleet_type',
                                '=',
                                'a.fleet_type'
                            )
                            ->on(
                                'd.pc_number',
                                '=',
                                'a.pc_number'
                            );
                    }
                )
                ->select(
                    'd.id as device_id',
                    'n.body',
                    'n.created_at',
                    'n.updated_at'
                )
                ->get();

            foreach ($rows as $row) {
                DB::table('operator_device_notes')
                    ->insert([
                        'device_id' => $row->device_id,
                        'body' => $row->body,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_device_notes');
    }
};
