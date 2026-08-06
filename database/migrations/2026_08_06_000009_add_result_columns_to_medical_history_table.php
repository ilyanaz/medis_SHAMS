<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $historyFields = [
        'diagnosed_history',
        'medication_history',
        'admitted_history',
        'family_history',
        'others_history',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('medical_history')) {
            return;
        }

        Schema::table('medical_history', function (Blueprint $table): void {
            $previousColumn = 'diagnosed_history';

            foreach ([
                'diagnosed_history_result',
                'medication_history_result',
                'admitted_history_result',
                'family_history_result',
                'others_history_result',
            ] as $column) {
                if (! Schema::hasColumn('medical_history', $column)) {
                    $table->enum($column, ['Yes', 'No'])->nullable()->after($previousColumn);
                }

                $previousColumn = $column;
            }
        });

        foreach ($this->historyFields as $field) {
            $resultColumn = $field . '_result';

            if (! Schema::hasColumn('medical_history', $resultColumn) || ! Schema::hasColumn('medical_history', $field)) {
                continue;
            }

            DB::table('medical_history')
                ->whereNull($resultColumn)
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->update([$resultColumn => 'Yes']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('medical_history')) {
            return;
        }

        Schema::table('medical_history', function (Blueprint $table): void {
            foreach ([
                'diagnosed_history_result',
                'medication_history_result',
                'admitted_history_result',
                'family_history_result',
                'others_history_result',
            ] as $column) {
                if (Schema::hasColumn('medical_history', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
