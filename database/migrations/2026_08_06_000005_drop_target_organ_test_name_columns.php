<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('target_organ')) {
            return;
        }

        Schema::table('target_organ', function (Blueprint $table): void {
            $columnsToDrop = [];

            foreach ([
                'blood_count',
                'renal_function',
                'liver_function',
                'chest_xray',
            ] as $column) {
                if (Schema::hasColumn('target_organ', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('target_organ')) {
            return;
        }

        Schema::table('target_organ', function (Blueprint $table): void {
            if (! Schema::hasColumn('target_organ', 'blood_count')) {
                $table->text('blood_count')->nullable()->after('target_id');
            }
            if (! Schema::hasColumn('target_organ', 'renal_function')) {
                $table->text('renal_function')->nullable()->after('blood_comments');
            }
            if (! Schema::hasColumn('target_organ', 'liver_function')) {
                $table->text('liver_function')->nullable()->after('renal_comments');
            }
            if (! Schema::hasColumn('target_organ', 'chest_xray')) {
                $table->text('chest_xray')->nullable()->after('liver_comments');
            }
        });
    }
};
