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

            foreach ($this->historyFields as $field) {
                $resultColumn = $field . '_result';

                if (! Schema::hasColumn('medical_history', $resultColumn)) {
                    $table->enum($resultColumn, ['Yes', 'No'])->nullable()->after($previousColumn);
                }

                $previousColumn = $resultColumn;
            }
        });

        foreach ($this->historyFields as $field) {
            $resultColumn = $field . '_result';

            if (! Schema::hasColumn('medical_history', $field) || ! Schema::hasColumn('medical_history', $resultColumn)) {
                continue;
            }

            DB::table('medical_history')
                ->whereNull($resultColumn)
                ->where(function ($query) use ($field): void {
                    $query->whereRaw('UPPER(TRIM(`' . $field . '`)) IN (?, ?)', ['NO', 'NO MEDICAL ILLNESS']);
                })
                ->update([$resultColumn => 'No']);

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
            foreach ($this->historyFields as $field) {
                $resultColumn = $field . '_result';

                if (Schema::hasColumn('medical_history', $resultColumn)) {
                    $table->dropColumn($resultColumn);
                }
            }
        });
    }
};
