<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forbidden_behaviours', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id')->nullable()->after('id');
            $table->foreign('chat_agent_id')->references('id')->on('chat_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forbidden_behaviours', function (Blueprint $table) {
            $table->dropForeign(['chat_agent_id']);
            $table->dropColumn('chat_agent_id');
        });
    }
};
