<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('instruction');
            $table->enum('level', ['info', 'warning', 'danger'])->default('warning');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_cases');
    }
};
