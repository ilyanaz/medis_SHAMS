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
            Schema::create('target_organ_other_tests', function (Blueprint $table): void {
                $table->bigIncrements('other_target_test_id');
                $table->unsignedBigInteger('target_id')->nullable();
                $table->unsignedBigInteger('surveillance_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->text('test_name')->nullable();
                $table->enum('result', ['Normal', 'Abnormal'])->nullable();
                $table->text('comments')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('target_id');
                $table->index('surveillance_id');
                $table->index('employee_id');
                $table->index('result');
            });
        }

        if (! Schema::hasTable('target_organ') || ! Schema::hasColumn('target_organ', 'other_tests')) {
            return;
        }

        $targetOrgans = DB::table('target_organ')
            ->select('target_id', 'surveillance_id', 'employee_id', 'other_tests')
            ->whereNotNull('other_tests')
            ->where('other_tests', '!=', '')
            ->get();

        foreach ($targetOrgans as $targetOrgan) {
            $existingRows = DB::table('target_organ_other_tests')
                ->where('target_id', $targetOrgan->target_id)
                ->count();

            if ($existingRows > 0) {
                continue;
            }

            $decodedRows = json_decode((string) $targetOrgan->other_tests, true);
            if (! is_array($decodedRows)) {
                continue;
            }

            $payload = [];
            foreach (array_values($decodedRows) as $index => $row) {
                $testName = trim((string) ($row['name'] ?? ''));
                $result = trim((string) ($row['result'] ?? ''));
                $comments = trim((string) ($row['comments'] ?? ''));

                if ($testName === '' && $result === '' && $comments === '') {
                    continue;
                }

                $payload[] = [
                    'target_id' => $targetOrgan->target_id,
                    'surveillance_id' => $targetOrgan->surveillance_id,
                    'employee_id' => $targetOrgan->employee_id,
                    'test_name' => $testName !== '' ? $testName : null,
                    'result' => $result !== '' ? $result : null,
                    'comments' => $comments !== '' ? $comments : null,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($payload !== []) {
                DB::table('target_organ_other_tests')->insert($payload);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('target_organ_other_tests');
    }
};
