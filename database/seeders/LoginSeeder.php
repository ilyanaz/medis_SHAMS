<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LoginSeeder extends Seeder
{
    /**
     * Seed the default login account.
     */
    public function run(): void
    {
        $table = DB::table('users');
        $primaryKey = Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id';

        $data = [
            'username' => 'ilyana',
            'email' => 'ilyanazahirakbse@gmail.com',
            'password' => Hash::make('Med!s1243'),
            'role' => 'Doctor',
        ];

        if (Schema::hasColumn('users', 'name')) {
            $data['name'] = 'ilyana';
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $data['email_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'updated_at')) {
            $data['updated_at'] = now();
        }

        $existingUser = $table
            ->where('username', $data['username'])
            ->orWhere('email', $data['email'])
            ->first();

        if ($existingUser) {
            $table->where($primaryKey, $existingUser->{$primaryKey})->update($data);

            return;
        }

        if (Schema::hasColumn('users', 'created_at')) {
            $data['created_at'] = now();
        }

        $table->insert($data);
    }
}
