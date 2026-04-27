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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30);
            $table->string('platform_user_id');
            $table->string('phone_number')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('total_messages')->default(0);
            $table->json('tags')->nullable();
            $table->string('mode', 20)->default('bot');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->text('escalation_summary')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'platform_user_id']);
            $table->index('phone_number');
            $table->index('last_seen_at');
            $table->index('assigned_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
