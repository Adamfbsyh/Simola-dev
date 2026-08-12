<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('operator_note_transfer_items')) {
            return;
        }

        Schema::create(
            'operator_note_transfer_items',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('transfer_request_id')
                    ->constrained('operator_note_transfer_requests')
                    ->cascadeOnDelete();
                $table->foreignId('source_note_id')
                    ->nullable()
                    ->constrained('operator_device_notes')
                    ->nullOnDelete();
                $table->text('snapshot_body');
                $table->boolean('is_approved')->nullable()->index();
                $table->timestamps();

                $table->index(
                    ['transfer_request_id', 'is_approved'],
                    'operator_transfer_items_review_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_note_transfer_items');
    }
};
