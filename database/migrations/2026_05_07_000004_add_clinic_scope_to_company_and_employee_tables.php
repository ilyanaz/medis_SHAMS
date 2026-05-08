<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company') && ! Schema::hasColumn('company', 'clinic_id')) {
            Schema::table('company', function (Blueprint $table): void {
                $table->unsignedInteger('clinic_id')->nullable()->after('company_id')->index();
            });
        }

        if (Schema::hasTable('employee') && ! Schema::hasColumn('employee', 'clinic_id')) {
            Schema::table('employee', function (Blueprint $table): void {
                $table->unsignedInteger('clinic_id')->nullable()->after('employee_id')->index();
            });
        }

        if (Schema::hasTable('employee') && ! Schema::hasColumn('employee', 'company_id')) {
            Schema::table('employee', function (Blueprint $table): void {
                $table->unsignedInteger('company_id')->nullable()->after('clinic_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee')) {
            Schema::table('employee', function (Blueprint $table): void {
                if (Schema::hasColumn('employee', 'company_id')) {
                    $table->dropColumn('company_id');
                }
                if (Schema::hasColumn('employee', 'clinic_id')) {
                    $table->dropColumn('clinic_id');
                }
            });
        }

        if (Schema::hasTable('company') && Schema::hasColumn('company', 'clinic_id')) {
            Schema::table('company', function (Blueprint $table): void {
                $table->dropColumn('clinic_id');
            });
        }
    }
};
