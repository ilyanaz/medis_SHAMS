<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_work_units')) {
            return;
        }

        Schema::create('company_work_units', function (Blueprint $table): void {
            $table->bigIncrements('work_unit_id');
            $table->unsignedInteger('company_id')->index();
            $table->string('work_unit_name', 150)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_work_units');
    }
};
