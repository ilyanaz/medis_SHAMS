<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('target_organ') || Schema::hasColumn('target_organ', 'other_tests')) {
            return;
        }

        Schema::table('target_organ', function (Blueprint $table): void {
            $table->longText('other_tests')->nullable()->after('spirometry_comments');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('target_organ') || ! Schema::hasColumn('target_organ', 'other_tests')) {
            return;
        }

        Schema::table('target_organ', function (Blueprint $table): void {
            $table->dropColumn('other_tests');
        });
    }
};
