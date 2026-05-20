<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recommendation')) {
            return;
        }

        Schema::table('recommendation', function (Blueprint $table): void {
            if (! Schema::hasColumn('recommendation', 'is_final')) {
                $table->boolean('is_final')->default(false)->after('clinic_email');
            }

            if (! Schema::hasColumn('recommendation', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('is_final');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recommendation')) {
            return;
        }

        Schema::table('recommendation', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['is_final', 'finalized_at'],
                static fn ($column) => Schema::hasColumn('recommendation', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
