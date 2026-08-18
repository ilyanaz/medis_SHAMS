<?php

use Illuminate\Database\Migrations\Migration;
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

        foreach ($this->historyFields as $field) {
            $resultColumn = $field . '_result';

            if (! Schema::hasColumn('medical_history', $field) || ! Schema::hasColumn('medical_history', $resultColumn)) {
                continue;
            }

            DB::table('medical_history')
                ->whereRaw('UPPER(TRIM(`' . $field . '`)) IN (?, ?)', ['NO', 'NO MEDICAL ILLNESS'])
                ->update([$resultColumn => 'No']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('medical_history')) {
            return;
        }

        foreach ($this->historyFields as $field) {
            $resultColumn = $field . '_result';

            if (! Schema::hasColumn('medical_history', $field) || ! Schema::hasColumn('medical_history', $resultColumn)) {
                continue;
            }

            DB::table('medical_history')
                ->whereRaw('UPPER(TRIM(`' . $field . '`)) IN (?, ?)', ['NO', 'NO MEDICAL ILLNESS'])
                ->where($resultColumn, 'No')
                ->update([$resultColumn => null]);
        }
    }
};
