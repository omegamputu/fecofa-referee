<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefereeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            [
                'name' => 'Internationale',
                'slug' => Str::slug('internationale'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'Nationale',
                'slug' => Str::slug('nationale'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'UNE',
                'slug' => Str::slug('une'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'Stagiaire',
                'slug' => Str::slug('stagiaire'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'SN',
                'slug' => Str::slug('sn'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'SA',
                'slug' => Str::slug('sa'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'STAT',
                'slug' => Str::slug('stat'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'STAG',
                'slug' => Str::slug('stag'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'S',
                'slug' => Str::slug('s'),
                'description' => '',
                'created_at' => now(),
            ],
            [
                'name' => 'CCL2',
                'slug' => Str::slug('ccl2'),
                'description' => '',
                'created_at' => now(),
            ]
        ];

        DB::table('referee_categories')->insert($categories);
    }
}
