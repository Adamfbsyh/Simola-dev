<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('operator_notes')) return;

        Schema::create('operator_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamps();
            $table->index(['user_id','updated_at'],'operator_note_user_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_notes');
    }
};
