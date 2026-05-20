<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company') && ! Schema::hasColumn('company', 'company_module')) {
            Schema::table('company', function (Blueprint $table): void {
                $table->string('company_module', 20)->nullable()->after('clinic_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company') && Schema::hasColumn('company', 'company_module')) {
            Schema::table('company', function (Blueprint $table): void {
                $table->dropColumn('company_module');
            });
        }
    }
};
