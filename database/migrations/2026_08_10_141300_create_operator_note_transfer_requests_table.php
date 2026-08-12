<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('operator_note_transfer_requests')) {
            return;
        }

        Schema::create(
            'operator_note_transfer_requests',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('source_device_id')
                    ->constrained('operator_devices')
                    ->cascadeOnDelete();
                $table->foreignId('target_device_id')
                    ->constrained('operator_devices')
                    ->cascadeOnDelete();
                $table->string('status', 20)
                    ->default('pending')
                    ->index();
                $table->timestamp('requested_at')->index();
                $table->foreignId('reviewed_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('review_note', 500)->nullable();
                $table->timestamps();

                $table->index(
                    ['source_device_id', 'status'],
                    'operator_transfer_source_status_idx'
                );
                $table->index(
                    ['target_device_id', 'status'],
                    'operator_transfer_target_status_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_note_transfer_requests');
    }
};
