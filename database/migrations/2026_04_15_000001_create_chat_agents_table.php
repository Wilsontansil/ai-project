<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->longText('system_prompt')->nullable();
            $table->string('model', 60)->default('gpt-4.1-mini');
            $table->unsignedInteger('max_tokens')->default(1000);
            $table->unsignedSmallInteger('max_history_messages')->default(20);
            $table->decimal('temperature', 2, 1)->default(0.7);
            $table->unsignedSmallInteger('message_await_seconds')->default(2);
            $table->string('timezone', 64)->default('UTC');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('escalation_condition')->nullable();
            $table->boolean('stop_ai_after_handoff')->default(false);
            $table->boolean('silent_handoff')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_agent_tool', function (Blueprint $table) {
            $table->foreignId('chat_agent_id')->constrained('chat_agents')->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['chat_agent_id', 'tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_agent_tool');
        Schema::dropIfExists('chat_agents');
    }
};
