<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class RefereeRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $roles = [
            [
                'name' => 'Arbitre',
                'slug' => Str::slug('arbitre'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'Arbitre assistant',
                'slug' => Str::slug('arbitre-assistant'),
                'description' => '',
                'created_at' => now(),
            ]
        ];

        DB::table('referee_roles')->insert($roles);
    }
}
