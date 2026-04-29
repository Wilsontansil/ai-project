<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('system_configs', 'source_type')) {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->string('source_type', 32)->default('manual')->after('description');
            });
        }

        if (!Schema::hasColumn('system_configs', 'data_model_id')) {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->foreignId('data_model_id')->nullable()->after('source_type')->constrained('data_models')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('system_configs', 'lookup_field')) {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->string('lookup_field', 191)->nullable()->after('data_model_id');
            });
        }

        if (!Schema::hasColumn('system_configs', 'lookup_value')) {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->text('lookup_value')->nullable()->after('lookup_field');
            });
        }

        if (!Schema::hasColumn('system_configs', 'result_field')) {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->string('result_field', 191)->nullable()->after('lookup_value');
            });
        }

        try {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->index('source_type');
                $table->index(['data_model_id', 'lookup_field']);
            });
        } catch (\Throwable) {
            // Ignore duplicate index creation in partially migrated environments.
        }
    }

    public function down(): void
    {
        Schema::table('system_configs', function (Blueprint $table) {
            try {
                $table->dropIndex(['data_model_id', 'lookup_field']);
            } catch (\Throwable) {
                // Ignore missing index in environments where migration partially ran.
            }

            try {
                $table->dropIndex(['source_type']);
            } catch (\Throwable) {
                // Ignore missing index in environments where migration partially ran.
            }
        });

        if (Schema::hasColumn('system_configs', 'data_model_id')) {
            Schema::table('system_configs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('data_model_id');
            });
        }

        $dropColumns = [];
        foreach (['source_type', 'lookup_field', 'lookup_value', 'result_field'] as $column) {
            if (Schema::hasColumn('system_configs', $column)) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns !== []) {
            Schema::table('system_configs', function (Blueprint $table) use ($dropColumns) {
                $table->dropColumn($dropColumns);
            });
        }
    }
};
