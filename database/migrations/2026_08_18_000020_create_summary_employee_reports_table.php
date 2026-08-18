<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('summary_employee_report')) {
            return;
        }

        Schema::create('summary_employee_report', function (Blueprint $table): void {
            $table->increments('summary_employee_report_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->text('chemical_name')->nullable();
            $table->unsignedInteger('doctor_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summary_employee_report');
    }
};
