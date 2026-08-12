<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('simola_help_articles')) {
            return;
        }

        Schema::create(
            'simola_help_articles',
            function (Blueprint $table): void {
                $table->id();
                $table->string('title', 180);
                $table->string('module', 100)->index();
                $table->json('keywords')->nullable();
                $table->text('content');
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(100);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index([
                    'is_active',
                    'sort_order',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('simola_help_articles');
    }
};
