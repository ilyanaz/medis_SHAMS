<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('removal_report')) {
            return;
        }

        Schema::table('removal_report', function (Blueprint $table): void {
            if (! Schema::hasColumn('removal_report', 'work_unit_department')) {
                $table->string('work_unit_department')->nullable()->after('health_hazard_present');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('removal_report') || ! Schema::hasColumn('removal_report', 'work_unit_department')) {
            return;
        }

        Schema::table('removal_report', function (Blueprint $table): void {
            $table->dropColumn('work_unit_department');
        });
    }
};
