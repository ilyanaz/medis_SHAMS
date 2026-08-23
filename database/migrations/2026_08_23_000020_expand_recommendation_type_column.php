<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recommendation') || ! Schema::hasColumn('recommendation', 'recommencation_type')) {
            return;
        }

        DB::statement("ALTER TABLE `recommendation` MODIFY `recommencation_type` TEXT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('recommendation') || ! Schema::hasColumn('recommendation', 'recommencation_type')) {
            return;
        }

        DB::statement("ALTER TABLE `recommendation` MODIFY `recommencation_type` VARCHAR(100) NULL");
    }
};
