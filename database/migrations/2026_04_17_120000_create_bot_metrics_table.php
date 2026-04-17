<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type', 30)->index();   // request, openai_call, tool_exec, outbound_http
            $table->string('channel', 40)->index();        // telegram, whatsapp, livechat, waha, tool-endpoint:*, etc.
            $table->json('meta')->nullable();               // flexible payload per metric type
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_metrics');
    }
};
