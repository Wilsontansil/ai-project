<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('tool_name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedBigInteger('data_model_id')->nullable();
            $table->json('parameters')->nullable();
            $table->json('endpoints')->nullable();
            $table->json('keywords')->nullable();
            $table->text('missing_message')->nullable();
            $table->json('information_text')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
