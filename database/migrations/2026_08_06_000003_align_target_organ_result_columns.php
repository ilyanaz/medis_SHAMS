<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('target_organ')) {
            return;
        }

        Schema::table('target_organ', function (Blueprint $table): void {
            if (! Schema::hasColumn('target_organ', 'blood_count_result')) {
                $table->enum('blood_count_result', ['Normal', 'Abnormal'])->nullable()->after('blood_count');
            }
            if (! Schema::hasColumn('target_organ', 'renal_function_result')) {
                $table->enum('renal_function_result', ['Normal', 'Abnormal'])->nullable()->after('renal_function');
            }
            if (! Schema::hasColumn('target_organ', 'liver_function_result')) {
                $table->enum('liver_function_result', ['Normal', 'Abnormal'])->nullable()->after('liver_function');
            }
            if (! Schema::hasColumn('target_organ', 'chest_xray_result')) {
                $table->enum('chest_xray_result', ['Normal', 'Abnormal'])->nullable()->after('chest_xray');
            }
        });

        DB::table('target_organ')
            ->whereNull('blood_count_result')
            ->whereIn('blood_count', ['Normal', 'Abnormal'])
            ->update(['blood_count_result' => DB::raw('blood_count')]);
        DB::table('target_organ')
            ->whereNull('renal_function_result')
            ->whereIn('renal_function', ['Normal', 'Abnormal'])
            ->update(['renal_function_result' => DB::raw('renal_function')]);
        DB::table('target_organ')
            ->whereNull('liver_function_result')
            ->whereIn('liver_function', ['Normal', 'Abnormal'])
            ->update(['liver_function_result' => DB::raw('liver_function')]);
        DB::table('target_organ')
            ->whereNull('chest_xray_result')
            ->whereIn('chest_xray', ['Normal', 'Abnormal'])
            ->update(['chest_xray_result' => DB::raw('chest_xray')]);

        DB::statement("ALTER TABLE `target_organ` MODIFY `blood_count` TEXT NULL");
        DB::statement("ALTER TABLE `target_organ` MODIFY `renal_function` TEXT NULL");
        DB::statement("ALTER TABLE `target_organ` MODIFY `liver_function` TEXT NULL");
        DB::statement("ALTER TABLE `target_organ` MODIFY `chest_xray` TEXT NULL");

        DB::table('target_organ')
            ->where(function ($query): void {
                $query->whereNull('blood_count')->orWhere('blood_count', '')->orWhereIn('blood_count', ['Normal', 'Abnormal']);
            })
            ->update(['blood_count' => 'Full Blood Count']);
        DB::table('target_organ')
            ->where(function ($query): void {
                $query->whereNull('renal_function')->orWhere('renal_function', '')->orWhereIn('renal_function', ['Normal', 'Abnormal']);
            })
            ->update(['renal_function' => 'Renal Function Test']);
        DB::table('target_organ')
            ->where(function ($query): void {
                $query->whereNull('liver_function')->orWhere('liver_function', '')->orWhereIn('liver_function', ['Normal', 'Abnormal']);
            })
            ->update(['liver_function' => 'Liver Function Test']);
        DB::table('target_organ')
            ->where(function ($query): void {
                $query->whereNull('chest_xray')->orWhere('chest_xray', '')->orWhereIn('chest_xray', ['Normal', 'Abnormal']);
            })
            ->update(['chest_xray' => 'Chest X-ray']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('target_organ')) {
            return;
        }

        DB::statement("ALTER TABLE `target_organ` MODIFY `blood_count` ENUM('Normal','Abnormal') NULL");
        DB::statement("ALTER TABLE `target_organ` MODIFY `renal_function` ENUM('Normal','Abnormal') NULL");
        DB::statement("ALTER TABLE `target_organ` MODIFY `liver_function` ENUM('Normal','Abnormal') NULL");
        DB::statement("ALTER TABLE `target_organ` MODIFY `chest_xray` ENUM('Normal','Abnormal') NULL");

        DB::table('target_organ')->whereNotNull('blood_count_result')->update(['blood_count' => DB::raw('blood_count_result')]);
        DB::table('target_organ')->whereNotNull('renal_function_result')->update(['renal_function' => DB::raw('renal_function_result')]);
        DB::table('target_organ')->whereNotNull('liver_function_result')->update(['liver_function' => DB::raw('liver_function_result')]);
        DB::table('target_organ')->whereNotNull('chest_xray_result')->update(['chest_xray' => DB::raw('chest_xray_result')]);

        Schema::table('target_organ', function (Blueprint $table): void {
            foreach ([
                'blood_count_result',
                'renal_function_result',
                'liver_function_result',
                'chest_xray_result',
            ] as $column) {
                if (Schema::hasColumn('target_organ', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
