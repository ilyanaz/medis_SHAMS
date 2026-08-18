<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('summary_report') || ! Schema::hasColumn('summary_report', 'chemical_name')) {
            return;
        }

        DB::statement('ALTER TABLE `summary_report` MODIFY `chemical_name` TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('summary_report') || ! Schema::hasColumn('summary_report', 'chemical_name')) {
            return;
        }

        DB::statement('ALTER TABLE `summary_report` MODIFY `chemical_name` VARCHAR(150) NULL');
    }
};
