<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fitness_report')) {
            return;
        }

        Schema::table('fitness_report', function (Blueprint $table): void {
            if (! Schema::hasColumn('fitness_report', 'doctor_practice_address')) {
                $table->text('doctor_practice_address')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('fitness_report', 'doctor_email_address')) {
                $table->string('doctor_email_address')->nullable()->after('doctor_practice_address');
            }
            if (! Schema::hasColumn('fitness_report', 'doctor_telephone')) {
                $table->string('doctor_telephone')->nullable()->after('doctor_email_address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fitness_report')) {
            return;
        }

        Schema::table('fitness_report', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('fitness_report', 'doctor_practice_address') ? 'doctor_practice_address' : null,
                Schema::hasColumn('fitness_report', 'doctor_email_address') ? 'doctor_email_address' : null,
                Schema::hasColumn('fitness_report', 'doctor_telephone') ? 'doctor_telephone' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
