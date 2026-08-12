<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('operator_chat_threads')) return;

        Schema::create('operator_chat_threads', function (Blueprint $table): void {
            $table->id();
            $table->string('fleet_type',40);
            $table->unsignedSmallInteger('pc_number');
            $table->string('status',20)->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->foreignId('last_message_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fleet_type','pc_number'],'operator_chat_thread_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_chat_threads');
    }
};
