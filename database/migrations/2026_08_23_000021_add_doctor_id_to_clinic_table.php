<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinic') || Schema::hasColumn('clinic', 'doctor_id')) {
            return;
        }

        Schema::table('clinic', function (Blueprint $table): void {
            $table->unsignedInteger('doctor_id')->nullable()->after('clinic_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clinic') || ! Schema::hasColumn('clinic', 'doctor_id')) {
            return;
        }

        Schema::table('clinic', function (Blueprint $table): void {
            $table->dropColumn('doctor_id');
        });
    }
};
