<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic', function (Blueprint $table): void {
            if (! Schema::hasColumn('clinic', 'clinic_status')) {
                $table->string('clinic_status', 20)->default('active')->after('clinic_username');
            }
        });

        Schema::table('doctor', function (Blueprint $table): void {
            if (! Schema::hasColumn('doctor', 'doctor_status')) {
                $table->string('doctor_status', 20)->default('active')->after('doctor_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinic', function (Blueprint $table): void {
            if (Schema::hasColumn('clinic', 'clinic_status')) {
                $table->dropColumn('clinic_status');
            }
        });

        Schema::table('doctor', function (Blueprint $table): void {
            if (Schema::hasColumn('doctor', 'doctor_status')) {
                $table->dropColumn('doctor_status');
            }
        });
    }
};
