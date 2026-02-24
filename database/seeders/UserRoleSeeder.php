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
                'id' => Str::uuid(),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'superadmin',
                'user_type' => 'internal',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Admin
            [
                'id' => Str::uuid(),
                'first_name' => 'System',
                'last_name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'user_type' => 'internal',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Manager
            [
                'id' => Str::uuid(),
                'first_name' => 'Project',
                'last_name' => 'Manager',
                'email' => 'manager@example.com',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'user_type' => 'internal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
