<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usechh5ii_reports') && ! Schema::hasTable('abnormal_report')) {
            DB::statement('RENAME TABLE `usechh5ii_reports` TO `abnormal_report`');
        }

        if (Schema::hasTable('usechh5ii_report_items') && ! Schema::hasTable('abnormal_report_items')) {
            DB::statement('RENAME TABLE `usechh5ii_report_items` TO `abnormal_report_items`');
        }

        if (Schema::hasTable('abnormal_report') && Schema::hasColumn('abnormal_report', 'usechh5ii_report_id')) {
            DB::statement('ALTER TABLE `abnormal_report` CHANGE `usechh5ii_report_id` `abnormal_report_id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('abnormal_report_items') && Schema::hasColumn('abnormal_report_items', 'usechh5ii_report_item_id')) {
            DB::statement('ALTER TABLE `abnormal_report_items` CHANGE `usechh5ii_report_item_id` `abnormal_report_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('abnormal_report_items') && Schema::hasColumn('abnormal_report_items', 'usechh5ii_report_id')) {
            DB::statement('ALTER TABLE `abnormal_report_items` CHANGE `usechh5ii_report_id` `abnormal_report_id` INT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('abnormal_report_items') && Schema::hasColumn('abnormal_report_items', 'abnormal_report_id')) {
            DB::statement('ALTER TABLE `abnormal_report_items` CHANGE `abnormal_report_id` `usechh5ii_report_id` INT UNSIGNED NOT NULL');
        }

        if (Schema::hasTable('abnormal_report_items') && Schema::hasColumn('abnormal_report_items', 'abnormal_report_item_id')) {
            DB::statement('ALTER TABLE `abnormal_report_items` CHANGE `abnormal_report_item_id` `usechh5ii_report_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('abnormal_report') && Schema::hasColumn('abnormal_report', 'abnormal_report_id')) {
            DB::statement('ALTER TABLE `abnormal_report` CHANGE `abnormal_report_id` `usechh5ii_report_id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('abnormal_report_items') && ! Schema::hasTable('usechh5ii_report_items')) {
            DB::statement('RENAME TABLE `abnormal_report_items` TO `usechh5ii_report_items`');
        }

        if (Schema::hasTable('abnormal_report') && ! Schema::hasTable('usechh5ii_reports')) {
            DB::statement('RENAME TABLE `abnormal_report` TO `usechh5ii_reports`');
        }
    }
};
