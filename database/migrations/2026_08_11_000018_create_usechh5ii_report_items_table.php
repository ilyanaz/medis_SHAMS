<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('abnormal_report_items')) {
            return;
        }

        Schema::create('abnormal_report_items', function (Blueprint $table): void {
            $table->increments('abnormal_report_item_id');
            $table->unsignedInteger('abnormal_report_id');
            $table->unsignedInteger('declaration_id')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('surveillance_id')->nullable();
            $table->text('patient_name')->nullable();
            $table->text('identity_no')->nullable();
            $table->string('sex', 50)->nullable();
            $table->text('designation')->nullable();
            $table->text('assessment_type')->nullable();
            $table->text('history_effect')->nullable();
            $table->text('clinical_findings')->nullable();
            $table->text('target_organ_function')->nullable();
            $table->text('bm_determinant')->nullable();
            $table->text('work_relatedness')->nullable();
            $table->text('recommendation_action')->nullable();
            $table->text('conclusion')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abnormal_report_items');
    }
};
