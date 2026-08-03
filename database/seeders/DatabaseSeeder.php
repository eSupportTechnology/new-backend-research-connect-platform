<?php

namespace Database\Seeders;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events must stay enabled here: User, Student, and ParentModel
     * all generate their UUID primary key in a `creating` event hook, which
     * WithoutModelEvents would silently suppress, breaking every insert
     * that relies on Eloquent (as opposed to a raw DB::table()->insert()
     * with an explicit id, like UserRoleSeeder uses).
     */
    public function run(): void
    {
        $this->call([
            UserRoleSeeder::class,
            SchoolStudentSeeder::class,
            HubCardSeeder::class,
        ]);
    }
}
