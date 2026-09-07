<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('doctor_id')->nullable()->unique();
        });
        if (! Schema::hasTable('doctor')) {
            return;
        }
        $candidates = [];
        foreach (DB::table('users')->get() as $user) {
            $ids = DB::table('doctor')->where(function ($query) use ($user) {
                $query->whereRaw('1 = 0');
                if (trim((string) ($user->email ?? '')) !== '') {
                    $query->orWhere('doctor_email', $user->email);
                }
                if (trim((string) ($user->username ?? '')) !== '') {
                    $query->orWhere('doctor_username', $user->username);
                }
            })->pluck('doctor_id');
            if ($ids->count() === 1) {
                $candidates[$user->user_id ?? $user->id] = (int) $ids->first();
            }
        }
        $counts = array_count_values($candidates);
        foreach ($candidates as $userId => $doctorId) {
            if ($counts[$doctorId] === 1) {
                DB::table('users')->where(Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id', $userId)->update(['doctor_id' => $doctorId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['doctor_id']);
            $table->dropColumn('doctor_id');
        });
    }
};
