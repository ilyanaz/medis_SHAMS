<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('summary_employee_report_items')) {
            return;
        }

        Schema::create('summary_employee_report_items', function (Blueprint $table): void {
            $table->increments('summary_employee_report_item_id');
            $table->unsignedInteger('summary_employee_report_id');
            $table->unsignedInteger('declaration_id')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('surveillance_id')->nullable();
            $table->date('ms_date')->nullable();
            $table->text('assessment_type')->nullable();
            $table->text('history_effect')->nullable();
            $table->text('clinical_findings')->nullable();
            $table->text('target_organ_function')->nullable();
            $table->text('bei_determinants')->nullable();
            $table->text('work_relatedness')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('mrp_date')->nullable();
            $table->text('doctor_name')->nullable();
            $table->text('doctor_registration_no')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summary_employee_report_items');
    }
};
