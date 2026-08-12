<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('operator_pc_assignments')) return;

        Schema::create('operator_pc_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('fleet_type',40)->index();
            $table->unsignedSmallInteger('pc_number')->index();
            $table->string('label',100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['fleet_type','pc_number','is_active'],'operator_pc_assignment_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_pc_assignments');
    }
};
