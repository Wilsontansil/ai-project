<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_agent_id')->nullable();
            $table->string('title');
            $table->text('instruction');
            $table->enum('type', ['guideline', 'forbidden'])->default('guideline');
            $table->string('category', 50)->default('behavior');
            $table->enum('level', ['info', 'warning', 'danger'])->default('warning');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('chat_agent_id')->references('id')->on('chat_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_rules');
    }
};
