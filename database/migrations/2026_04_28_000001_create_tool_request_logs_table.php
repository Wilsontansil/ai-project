<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tool_name', 100);
            $table->string('display_name', 150)->nullable();
            $table->string('endpoint_url', 500);
            $table->json('request_payload')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->float('latency_ms', 8, 2)->default(0);
            $table->boolean('success')->default(false);
            $table->string('error', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tool_name', 'created_at']);
            $table->index(['success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_request_logs');
    }
};
