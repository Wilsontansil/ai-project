<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->nullable();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->json('tags')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->string('source', 50)->default('manual');
            $table->string('source_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('confidence_score');
            $table->index('created_at');
        });

        Schema::create('ai_learned_memories', function (Blueprint $table) {
            $table->id();
            $table->text('pattern');
            $table->text('learned_response');
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->decimal('confidence', 5, 2)->default(0.50);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_approved']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_learned_memories');
        Schema::dropIfExists('knowledge_base');
    }
};
