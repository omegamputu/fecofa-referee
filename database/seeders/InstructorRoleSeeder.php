<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstructorRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $roles = [
            [
                'name' => 'Technique',
                'slug' => Str::slug('technique'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'Physique',
                'slug' => Str::slug('physique'),
                'description' => '',
                'created_at' => now(),
            ]
        ];

        DB::table('instructor_roles')->insert($roles);
    }
}
