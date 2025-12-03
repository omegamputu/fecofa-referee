<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Referees\Referee;
use App\Models\League;
use App\Models\Referees\RefereeRole;
use App\Models\Referees\RefereeCategory;
use Faker\Factory as Faker;

class RefereeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        
        $faker = Faker::create('fr_FR');

        // Récupère les IDs existants
        $leagueIds = League::pluck('id')->toArray();
        $roleIds = RefereeRole::pluck('id')->toArray();
        $categoryIds = RefereeCategory::pluck('id')->toArray();

        // Si aucune donnée n’existe, on évite les erreurs
        if (empty($leagueIds) || empty($roleIds) || empty($categoryIds)) {
            $this->command->error("❌ Impossible de lancer RefereeSeeder : des tables liées sont vides.");
            return;
        }

        for ($i = 1; $i <= 70; $i++) {

            // Génère un ID personnel unique style FECOFA
            $personId = "REF-" . str_pad($i, 5, "0", STR_PAD_LEFT);

            Referee::create([
                'league_id'             => $faker->randomElement($leagueIds),
                'referee_role_id'       => $faker->randomElement($roleIds),
                'referee_category_id'   => $faker->randomElement($categoryIds),

                'person_id'             => $personId,
                'last_name'             => strtoupper($faker->lastName()),
                'first_name'            => ucfirst($faker->firstName()),
                'date_of_birth'         => $faker->dateTimeBetween('-45 years', '-18 years'),
                'gender'                => $faker->randomElement(['male', 'female']),
                'phone'                 => $faker->phoneNumber(),
                'email'                 => $faker->unique()->safeEmail(),
                'address'               => $faker->address(),
                'education_level'       => $faker->randomElement(['Secondaire', 'Graduat', 'Licence', 'Master']),
                'profession'            => $faker->randomElement(['Enseignant', 'Comptable', 'Agent public', 'Coach', 'Entrepreneur']),
                'start_year'            => $faker->numberBetween(2005, 2024),
            ]);
        }

        $this->command->info("✅ 20 arbitres ont été générés avec succès !");
    }
}
