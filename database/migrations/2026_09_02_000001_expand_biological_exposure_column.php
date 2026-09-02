<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('biological_monitoring') || ! Schema::hasColumn('biological_monitoring', 'biological_exposure')) {
            return;
        }

        DB::statement('ALTER TABLE biological_monitoring MODIFY biological_exposure TEXT NULL');
    }

    public function down(): void
    {
        // Do not narrow this column again: valid determinant text would be lost.
    }
};
