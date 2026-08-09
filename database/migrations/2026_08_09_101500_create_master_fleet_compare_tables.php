<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('master_fleet_compare_batches')) {
            Schema::create('master_fleet_compare_batches', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('fleet_type', 32)->index();
                $table->string('original_name');
                $table->string('stored_path');
                $table->string('source_hash', 64)->index();
                $table->string('status', 32)->default('review')->index();
                $table->string('sheet_name')->nullable();
                $table->unsignedInteger('header_row')->nullable();
                $table->json('header_map')->nullable();
                $table->json('summary')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->unsignedBigInteger('applied_by')->nullable()->index();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('master_fleet_compare_rows')) {
            Schema::create('master_fleet_compare_rows', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('batch_id')
                    ->constrained('master_fleet_compare_batches')
                    ->cascadeOnDelete();
                $table->unsignedInteger('source_row')->nullable();
                $table->string('status', 32)->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->string('plate_number', 50)->nullable()->index();
                $table->string('unit_code', 100)->nullable()->index();
                $table->json('source_data')->nullable();
                $table->json('current_data')->nullable();
                $table->json('proposed_data')->nullable();
                $table->json('diff_data')->nullable();
                $table->boolean('can_apply')->default(false)->index();
                $table->string('apply_status', 32)->default('pending')->index();
                $table->text('apply_message')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
                $table->index(['batch_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_fleet_compare_rows');
        Schema::dropIfExists('master_fleet_compare_batches');
    }
};
