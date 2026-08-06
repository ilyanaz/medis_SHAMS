<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('physical_examination') || ! Schema::hasColumn('physical_examination', 'general_appearances')) {
            return;
        }

        DB::table('physical_examination')
            ->whereNotNull('general_appearances')
            ->whereRaw('TRIM(`general_appearances`) <> ""')
            ->update([
                'general_appearances' => DB::raw("CASE WHEN LOWER(TRIM(`general_appearances`)) = 'normal' THEN 'Normal' ELSE 'Abnormal' END"),
            ]);

        DB::statement("ALTER TABLE `physical_examination` MODIFY `general_appearances` ENUM('Normal','Abnormal') NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('physical_examination') || ! Schema::hasColumn('physical_examination', 'general_appearances')) {
            return;
        }

        DB::statement("ALTER TABLE `physical_examination` MODIFY `general_appearances` TEXT NULL");
    }
};
