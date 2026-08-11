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
            if (! Schema::hasColumn('removal_report', 'worker_identity_no')) {
                $table->string('worker_identity_no')->nullable()->after('removal_type');
            }
            if (! Schema::hasColumn('removal_report', 'worker_date_of_birth')) {
                $table->date('worker_date_of_birth')->nullable()->after('worker_identity_no');
            }
            if (! Schema::hasColumn('removal_report', 'worker_sex')) {
                $table->string('worker_sex')->nullable()->after('worker_date_of_birth');
            }
            if (! Schema::hasColumn('removal_report', 'company_name_address')) {
                $table->text('company_name_address')->nullable()->after('worker_sex');
            }
            if (! Schema::hasColumn('removal_report', 'employment_start_date')) {
                $table->date('employment_start_date')->nullable()->after('company_name_address');
            }
            if (! Schema::hasColumn('removal_report', 'employment_duration_text')) {
                $table->string('employment_duration_text')->nullable()->after('employment_start_date');
            }
            if (! Schema::hasColumn('removal_report', 'health_hazard_present')) {
                $table->text('health_hazard_present')->nullable()->after('employment_duration_text');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('removal_report')) {
            return;
        }

        Schema::table('removal_report', function (Blueprint $table): void {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('removal_report', 'worker_identity_no') ? 'worker_identity_no' : null,
                Schema::hasColumn('removal_report', 'worker_date_of_birth') ? 'worker_date_of_birth' : null,
                Schema::hasColumn('removal_report', 'worker_sex') ? 'worker_sex' : null,
                Schema::hasColumn('removal_report', 'company_name_address') ? 'company_name_address' : null,
                Schema::hasColumn('removal_report', 'employment_start_date') ? 'employment_start_date' : null,
                Schema::hasColumn('removal_report', 'employment_duration_text') ? 'employment_duration_text' : null,
                Schema::hasColumn('removal_report', 'health_hazard_present') ? 'health_hazard_present' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
