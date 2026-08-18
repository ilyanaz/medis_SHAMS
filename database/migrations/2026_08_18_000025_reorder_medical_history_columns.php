<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medical_history')) {
            return;
        }

        $requiredColumns = [
            'medHistory_id',
            'diagnosed_history_result',
            'diagnosed_history',
            'medication_history_result',
            'medication_history',
            'admitted_history_result',
            'admitted_history',
            'family_history_result',
            'family_history',
            'others_history_result',
            'others_history',
            'employee_id',
            'surveillance_id',
        ];

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('medical_history', $column)) {
                return;
            }
        }

        DB::statement("ALTER TABLE `medical_history` MODIFY `diagnosed_history_result` ENUM('Yes','No') NULL AFTER `medHistory_id`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `diagnosed_history` TEXT NULL AFTER `diagnosed_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `medication_history_result` ENUM('Yes','No') NULL AFTER `diagnosed_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `medication_history` TEXT NULL AFTER `medication_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `admitted_history_result` ENUM('Yes','No') NULL AFTER `medication_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `admitted_history` TEXT NULL AFTER `admitted_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `family_history_result` ENUM('Yes','No') NULL AFTER `admitted_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `family_history` TEXT NULL AFTER `family_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `others_history_result` ENUM('Yes','No') NULL AFTER `family_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `others_history` TEXT NULL AFTER `others_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `employee_id` INT NULL AFTER `others_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `surveillance_id` INT NULL AFTER `employee_id`");
    }

    public function down(): void
    {
        if (! Schema::hasTable('medical_history')) {
            return;
        }

        $requiredColumns = [
            'medHistory_id',
            'employee_id',
            'surveillance_id',
            'diagnosed_history',
            'medication_history',
            'admitted_history',
            'family_history',
            'others_history',
            'diagnosed_history_result',
            'medication_history_result',
            'admitted_history_result',
            'family_history_result',
            'others_history_result',
        ];

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('medical_history', $column)) {
                return;
            }
        }

        DB::statement("ALTER TABLE `medical_history` MODIFY `employee_id` INT NULL AFTER `medHistory_id`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `surveillance_id` INT NULL AFTER `employee_id`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `diagnosed_history` TEXT NULL AFTER `surveillance_id`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `medication_history` TEXT NULL AFTER `diagnosed_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `admitted_history` TEXT NULL AFTER `medication_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `family_history` TEXT NULL AFTER `admitted_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `others_history` TEXT NULL AFTER `family_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `diagnosed_history_result` ENUM('Yes','No') NULL AFTER `others_history`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `medication_history_result` ENUM('Yes','No') NULL AFTER `diagnosed_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `admitted_history_result` ENUM('Yes','No') NULL AFTER `medication_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `family_history_result` ENUM('Yes','No') NULL AFTER `admitted_history_result`");
        DB::statement("ALTER TABLE `medical_history` MODIFY `others_history_result` ENUM('Yes','No') NULL AFTER `family_history_result`");
    }
};
