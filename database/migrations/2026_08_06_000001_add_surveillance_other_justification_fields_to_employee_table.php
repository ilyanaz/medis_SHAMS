<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee')) {
            return;
        }

        Schema::table('employee', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee', 'employee_ethnicity_other')) {
                $table->text('employee_ethnicity_other')->nullable()->after('employee_ethnicity');
            }

            if (! Schema::hasColumn('employee', 'employee_citizenship_other')) {
                $table->text('employee_citizenship_other')->nullable()->after('employee_citizenship');
            }

            if (! Schema::hasColumn('employee', 'employee_martial_other')) {
                $table->text('employee_martial_other')->nullable()->after('employee_martialStatus');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee')) {
            return;
        }

        Schema::table('employee', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('employee', 'employee_ethnicity_other')) {
                $columns[] = 'employee_ethnicity_other';
            }

            if (Schema::hasColumn('employee', 'employee_citizenship_other')) {
                $columns[] = 'employee_citizenship_other';
            }

            if (Schema::hasColumn('employee', 'employee_martial_other')) {
                $columns[] = 'employee_martial_other';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
