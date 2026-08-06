<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('occupational_history') || Schema::hasColumn('occupational_history', 'start_employment_date')) {
            return;
        }

        Schema::table('occupational_history', function (Blueprint $table): void {
            $table->date('start_employment_date')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('occupational_history') || ! Schema::hasColumn('occupational_history', 'start_employment_date')) {
            return;
        }

        Schema::table('occupational_history', function (Blueprint $table): void {
            $table->dropColumn('start_employment_date');
        });
    }
};
