<?php

namespace Database\Seeders;

use App\Models\Referees\Referee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RoleAndPermissionSeeder::class);
        $this->call(LeagueSeeder::class);
        $this->call(RefereeCategorySeeder::class);
        $this->call(RefereeRoleSeeder::class);
        //$this->call(RefereeSeeder::class);
    }
}
