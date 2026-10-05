<?php

namespace Database\Seeders;

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
                'name' => 'Stagiaire',
                'slug' => Str::slug('stagiaire'),
                'description' => '',
                'created_at' => now(),
            ],
        ];

        DB::table('referee_categories')->insert($categories);
    }
}
