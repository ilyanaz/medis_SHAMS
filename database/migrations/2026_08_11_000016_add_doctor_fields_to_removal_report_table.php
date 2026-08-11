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
            if (! Schema::hasColumn('removal_report', 'doctor_practice_address')) {
                $table->text('doctor_practice_address')->nullable()->after('work_unit_department');
            }
            if (! Schema::hasColumn('removal_report', 'doctor_email_address')) {
                $table->string('doctor_email_address')->nullable()->after('doctor_practice_address');
            }
            if (! Schema::hasColumn('removal_report', 'doctor_telephone')) {
                $table->string('doctor_telephone')->nullable()->after('doctor_email_address');
            }
            if (! Schema::hasColumn('removal_report', 'doctor_fax')) {
                $table->string('doctor_fax')->nullable()->after('doctor_telephone');
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
                Schema::hasColumn('removal_report', 'doctor_practice_address') ? 'doctor_practice_address' : null,
                Schema::hasColumn('removal_report', 'doctor_email_address') ? 'doctor_email_address' : null,
                Schema::hasColumn('removal_report', 'doctor_telephone') ? 'doctor_telephone' : null,
                Schema::hasColumn('removal_report', 'doctor_fax') ? 'doctor_fax' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
