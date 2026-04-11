<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('needs_human')->default(false)->after('tags');
            $table->text('escalation_reason')->nullable()->after('needs_human');
            $table->timestamp('escalated_at')->nullable()->after('escalation_reason');
            $table->timestamp('resolved_at')->nullable()->after('escalated_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['needs_human', 'escalation_reason', 'escalated_at', 'resolved_at']);
        });
    }
};
