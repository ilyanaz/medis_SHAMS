<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinic')) {
            return;
        }

        $columnsToDrop = [];

        foreach (['clinic_opening_hours', 'clinic_services', 'clinic_facility_type'] as $column) {
            if (Schema::hasColumn('clinic', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('clinic', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clinic')) {
            return;
        }

        Schema::table('clinic', function (Blueprint $table) {
            if (! Schema::hasColumn('clinic', 'clinic_opening_hours')) {
                $table->string('clinic_opening_hours')->nullable()->after('clinic_header_path');
            }

            if (! Schema::hasColumn('clinic', 'clinic_services')) {
                $table->string('clinic_services')->nullable()->after('clinic_opening_hours');
            }

            if (! Schema::hasColumn('clinic', 'clinic_facility_type')) {
                $table->string('clinic_facility_type', 100)->nullable()->after('clinic_services');
            }
        });
    }
};
