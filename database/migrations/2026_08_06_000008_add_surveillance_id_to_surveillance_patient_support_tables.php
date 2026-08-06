<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'medical_history',
            'occupational_history',
            'personal_social_history',
            'training_history',
        ] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'surveillance_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('surveillance_id')->nullable()->after('employee_id');
                $table->index('surveillance_id');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'medical_history',
            'occupational_history',
            'personal_social_history',
            'training_history',
        ] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'surveillance_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $indexName = $tableName . '_surveillance_id_index';
                $table->dropIndex($indexName);
                $table->dropColumn('surveillance_id');
            });
        }
    }
};
