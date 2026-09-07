<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinic') || Schema::hasColumn('clinic', 'user_id')) {
            return;
        }

        Schema::table('clinic', function (Blueprint $table): void {
            $table->unsignedInteger('user_id')->nullable()->after('clinic_id')->index();
        });

        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'doctor_id')) {
            return;
        }

        $primaryKey = Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id';
        $doctorOwners = DB::table('users')
            ->whereNotNull('doctor_id')
            ->pluck($primaryKey)
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($doctorOwners->count() === 1) {
            DB::table('clinic')
                ->whereNull('user_id')
                ->update(['user_id' => $doctorOwners->first()]);

            return;
        }

        $legacyDoctorAccountId = DB::table('users')
            ->where('username', 'doctor')
            ->value($primaryKey);

        if ($legacyDoctorAccountId) {
            DB::table('clinic')
                ->whereNull('user_id')
                ->update(['user_id' => (int) $legacyDoctorAccountId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('clinic') || ! Schema::hasColumn('clinic', 'user_id')) {
            return;
        }

        Schema::table('clinic', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });
    }
};
