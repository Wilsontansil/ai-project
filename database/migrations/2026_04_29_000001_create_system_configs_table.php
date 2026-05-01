<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->string('type')->nullable();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->string('source_type', 32)->default('manual');
            $table->foreignId('data_model_id')->nullable()->constrained('data_models')->nullOnDelete();
            $table->string('lookup_field', 191)->nullable();
            $table->text('lookup_value')->nullable();
            $table->string('result_field', 191)->nullable();
            $table->timestamps();

            $table->index('source_type');
            $table->index(['data_model_id', 'lookup_field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
