<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recommendation')) {
            return;
        }

        Schema::table('recommendation', function (Blueprint $table): void {
            if (! Schema::hasColumn('recommendation', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('surveillance_id');
            }
            if (! Schema::hasColumn('recommendation', 'clinic_id')) {
                $table->unsignedBigInteger('clinic_id')->nullable()->after('doctor_id');
            }
            if (! Schema::hasColumn('recommendation', 'employee_signature')) {
                $table->longText('employee_signature')->nullable()->after('clinic_id');
            }
            if (! Schema::hasColumn('recommendation', 'employee_signature_date')) {
                $table->date('employee_signature_date')->nullable()->after('employee_signature');
            }
            if (! Schema::hasColumn('recommendation', 'doctor_name')) {
                $table->string('doctor_name', 150)->nullable()->after('employee_signature_date');
            }
            if (! Schema::hasColumn('recommendation', 'doctor_registration_no')) {
                $table->string('doctor_registration_no', 100)->nullable()->after('doctor_name');
            }
            if (! Schema::hasColumn('recommendation', 'clinic_name')) {
                $table->string('clinic_name', 150)->nullable()->after('doctor_registration_no');
            }
            if (! Schema::hasColumn('recommendation', 'clinic_telephone')) {
                $table->string('clinic_telephone', 50)->nullable()->after('clinic_name');
            }
            if (! Schema::hasColumn('recommendation', 'clinic_fax')) {
                $table->string('clinic_fax', 50)->nullable()->after('clinic_telephone');
            }
            if (! Schema::hasColumn('recommendation', 'clinic_email')) {
                $table->string('clinic_email', 150)->nullable()->after('clinic_fax');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recommendation')) {
            return;
        }

        Schema::table('recommendation', function (Blueprint $table): void {
            $columns = [
                'doctor_id',
                'clinic_id',
                'employee_signature',
                'employee_signature_date',
                'doctor_name',
                'doctor_registration_no',
                'clinic_name',
                'clinic_telephone',
                'clinic_fax',
                'clinic_email',
            ];

            $dropColumns = array_values(array_filter($columns, static fn ($column) => Schema::hasColumn('recommendation', $column)));
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
