<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forbidden_behaviours', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id')->nullable()->after('id');
            $table->foreign('chat_agent_id')->references('id')->on('chat_agents')->nullOnDelete();
        });

        // Assign existing rules to the default agent
        $defaultAgentId = DB::table('chat_agents')->where('is_default', true)->value('id');
        if ($defaultAgentId) {
            DB::table('forbidden_behaviours')
                ->whereNull('chat_agent_id')
                ->update(['chat_agent_id' => $defaultAgentId]);
        }
    }

    public function down(): void
    {
        Schema::table('forbidden_behaviours', function (Blueprint $table) {
            $table->dropForeign(['chat_agent_id']);
            $table->dropColumn('chat_agent_id');
        });
    }
};
