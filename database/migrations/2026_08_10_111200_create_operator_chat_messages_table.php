<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('operator_chat_messages')) return;

        Schema::create('operator_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('operator_chat_threads')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type',20)->index();
            $table->text('body');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['thread_id','created_at'],'operator_chat_message_thread_time_idx');
            $table->index(['sender_type','read_at'],'operator_chat_message_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_chat_messages');
    }
};
