<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([

            // Super Admin
            [
                'id'         => Str::uuid(),
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'email'      => 'superadmin@example.com',
                'password'   => Hash::make('password123'),
                'role'       => 'superadmin',
                'user_type'  => 'internal',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Manager
            [
                'id'         => Str::uuid(),
                'first_name' => 'Manager',
                'last_name'  => 'Staff',
                'email'      => 'manager@example.com',
                'password'   => Hash::make('password123'),
                'role'       => 'manager',
                'user_type'  => 'internal',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Marketing
            [
                'id'         => Str::uuid(),
                'first_name' => 'Marketing',
                'last_name'  => 'Staff',
                'email'      => 'marketing@example.com',
                'password'   => Hash::make('password123'),
                'role'       => 'marketing',
                'user_type'  => 'internal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
