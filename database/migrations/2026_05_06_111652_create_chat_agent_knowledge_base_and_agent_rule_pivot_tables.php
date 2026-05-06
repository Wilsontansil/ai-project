<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: agent <-> knowledge_base
        Schema::create('chat_agent_knowledge_base', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id');
            $table->unsignedBigInteger('knowledge_base_id');
            $table->primary(['chat_agent_id', 'knowledge_base_id']);
            $table->foreign('chat_agent_id')->references('id')->on('chat_agents')->onDelete('cascade');
            $table->foreign('knowledge_base_id')->references('id')->on('knowledge_base')->onDelete('cascade');
        });

        // Pivot: agent <-> agent_rules
        Schema::create('chat_agent_agent_rule', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id');
            $table->unsignedBigInteger('agent_rule_id');
            $table->primary(['chat_agent_id', 'agent_rule_id']);
            $table->foreign('chat_agent_id')->references('id')->on('chat_agents')->onDelete('cascade');
            $table->foreign('agent_rule_id')->references('id')->on('agent_rules')->onDelete('cascade');
        });

        // Migrate existing KB rows: fill pivot from current chat_agent_id FK
        $kbRows = DB::table('knowledge_base')->whereNotNull('chat_agent_id')->get(['id', 'chat_agent_id']);
        foreach ($kbRows as $row) {
            DB::table('chat_agent_knowledge_base')->insertOrIgnore([
                'chat_agent_id'      => $row->chat_agent_id,
                'knowledge_base_id'  => $row->id,
            ]);
        }

        // Migrate existing AgentRule rows: fill pivot from current chat_agent_id FK
        $ruleRows = DB::table('agent_rules')->whereNotNull('chat_agent_id')->get(['id', 'chat_agent_id']);
        foreach ($ruleRows as $row) {
            DB::table('chat_agent_agent_rule')->insertOrIgnore([
                'chat_agent_id' => $row->chat_agent_id,
                'agent_rule_id' => $row->id,
            ]);
        }

        // Make chat_agent_id nullable (entries can now exist as "library" without an agent)
        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id')->nullable()->change();
        });

        Schema::table('agent_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id')->nullable(false)->change();
        });
        Schema::table('agent_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_agent_id')->nullable(false)->change();
        });
        Schema::dropIfExists('chat_agent_knowledge_base');
        Schema::dropIfExists('chat_agent_agent_rule');
    }
};
