<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('biological_monitoring') || Schema::hasColumn('biological_monitoring', 'manual_completed')) {
            return;
        }

        Schema::table('biological_monitoring', function (Blueprint $table): void {
            $table->boolean('manual_completed')->default(false)->after('blood_result_files');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('biological_monitoring') || ! Schema::hasColumn('biological_monitoring', 'manual_completed')) {
            return;
        }

        Schema::table('biological_monitoring', function (Blueprint $table): void {
            $table->dropColumn('manual_completed');
        });
    }
};
