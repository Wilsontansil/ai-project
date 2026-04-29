<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_agent_id')->constrained('chat_agents')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('source')->default('manual'); // 'manual', 'file', or 'datamodel'
            $table->string('file_name')->nullable();
            $table->foreignId('data_model_id')->nullable()->constrained('data_models')->nullOnDelete();
            $table->text('query_sql')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['chat_agent_id', 'title']);
            $table->index(['chat_agent_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base');
    }
};
