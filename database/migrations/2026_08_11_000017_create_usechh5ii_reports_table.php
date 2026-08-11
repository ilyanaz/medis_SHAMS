<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('abnormal_report')) {
            return;
        }

        Schema::create('abnormal_report', function (Blueprint $table): void {
            $table->increments('abnormal_report_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->date('examination_date')->nullable();
            $table->text('chemical_name')->nullable();
            $table->unsignedInteger('doctor_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abnormal_report');
    }
};
