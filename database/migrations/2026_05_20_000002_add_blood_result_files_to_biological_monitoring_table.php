<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('biological_monitoring') || Schema::hasColumn('biological_monitoring', 'blood_result_files')) {
            return;
        }

        Schema::table('biological_monitoring', function (Blueprint $table): void {
            $table->longText('blood_result_files')->nullable()->after('baseline_annual');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('biological_monitoring') || ! Schema::hasColumn('biological_monitoring', 'blood_result_files')) {
            return;
        }

        Schema::table('biological_monitoring', function (Blueprint $table): void {
            $table->dropColumn('blood_result_files');
        });
    }
};
