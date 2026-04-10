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
        Schema::create('customer_behaviors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('intent', 100)->nullable();
            $table->string('sentiment', 30)->nullable();
            $table->decimal('frequency_score', 8, 2)->default(0);
            $table->timestamp('last_intent_at')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index('intent');
            $table->index('sentiment');
            $table->index('last_intent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_behaviors');
    }
};
