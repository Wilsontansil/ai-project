<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->text('summary')->nullable();
            $table->enum('status', ['pending', 'scraped', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
