<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_case_logs', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 120)->index();
            $table->string('channel', 50)->index();
            $table->string('tool_name', 100)->index();
            $table->string('display_name', 150)->nullable();
            // How the tool was triggered: openai_tool_call | chain_resume | keyword | intent
            $table->string('trigger_mode', 30)->default('unknown');
            $table->json('arguments')->nullable();           // sanitized args sent to the tool
            $table->boolean('has_attachment')->default(false)->index();
            $table->string('attachment_url', 500)->nullable(); // image URL when source is remote
            $table->json('attachment_meta')->nullable();        // {source, mime} for base64 or {source} for url
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // from agentContext customer_profile
            $table->json('customer_info')->nullable();          // full customer_profile snapshot
            $table->text('tool_reply')->nullable();             // final reply returned to user (≤1000 chars)
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['tool_name', 'created_at']);
            $table->index(['channel', 'created_at']);
            $table->index(['chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_case_logs');
    }
};
