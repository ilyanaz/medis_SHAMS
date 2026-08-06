<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_email_logs', function (Blueprint $table): void {
            $table->id('report_email_log_id');
            $table->string('module', 50)->default('surveillance');
            $table->string('report_key', 50);
            $table->unsignedBigInteger('declaration_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('surveillance_id')->nullable();
            $table->string('recipient_email', 150)->nullable();
            $table->string('email_subject', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->unsignedBigInteger('sent_by_user_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'report_key'], 'report_email_logs_module_report_idx');
            $table->index(['declaration_id', 'employee_id', 'company_id', 'surveillance_id'], 'report_email_logs_record_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_email_logs');
    }
};
