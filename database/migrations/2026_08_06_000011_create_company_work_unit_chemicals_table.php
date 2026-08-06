<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_work_unit_chemicals')) {
            return;
        }

        Schema::create('company_work_unit_chemicals', function (Blueprint $table): void {
            $table->bigIncrements('work_unit_chemical_id');
            $table->unsignedBigInteger('work_unit_id')->index();
            $table->string('chemical_name', 150)->nullable();
            $table->string('chra_report_no', 150)->nullable();
            $table->unsignedInteger('total_workers')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_work_unit_chemicals');
    }
};
