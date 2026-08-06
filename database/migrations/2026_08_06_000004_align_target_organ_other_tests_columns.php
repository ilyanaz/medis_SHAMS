<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('target_organ_other_tests')) {
            return;
        }

        Schema::table('target_organ_other_tests', function (Blueprint $table): void {
            if (! Schema::hasColumn('target_organ_other_tests', 'test_name')) {
                $table->text('test_name')->nullable()->after('employee_id');
            }

            if (! Schema::hasColumn('target_organ_other_tests', 'result')) {
                $table->enum('result', ['Normal', 'Abnormal'])->nullable()->after('test_name');
            }

            if (! Schema::hasColumn('target_organ_other_tests', 'comments')) {
                $table->text('comments')->nullable()->after('result');
            }
        });

        $this->dropIndexIfExists('target_organ_other_tests', 'target_organ_other_tests_test_name_index');
        DB::statement("ALTER TABLE `target_organ_other_tests` MODIFY `test_name` TEXT NULL");
        DB::statement("ALTER TABLE `target_organ_other_tests` MODIFY `result` ENUM('Normal','Abnormal') NULL");
        DB::statement("ALTER TABLE `target_organ_other_tests` MODIFY `comments` TEXT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('target_organ_other_tests')) {
            return;
        }

        DB::statement("ALTER TABLE `target_organ_other_tests` MODIFY `test_name` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `target_organ_other_tests` MODIFY `result` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `target_organ_other_tests` MODIFY `comments` TEXT NULL");

        if (! $this->indexExists('target_organ_other_tests', 'target_organ_other_tests_test_name_index')) {
            Schema::table('target_organ_other_tests', function (Blueprint $table): void {
                $table->index('test_name');
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(sprintf('SHOW INDEX FROM `%s` WHERE Key_name = ?', $table), [$indexName]);

        return $rows !== [];
    }
};
