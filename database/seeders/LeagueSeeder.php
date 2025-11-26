<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $leagues = [
            [
                'name' => 'Ligue de Football de Kinshasa',
                'slug' => Str::slug('Ligue de Football de Kinshasa'),
                'code' => 'LIFKIN',
                'province' => 'Kinshasa',
                'headquarters' => '31, avenue de la Justice, Kinshasa Gombe',
                'contact_email' => 'liguedefootballdekinshasa@gmail.com',
                'contact_phone' => '',
            ],
            [
                'name' => 'Ligue de Football du Katanga',
                'slug' => Str::slug('Ligue de Football du Katanga'),
                'code' => 'LIFKAT',
                'province' => 'Katanga',
                'headquarters' => 'Lubumbashi / Stade Kibasa Maliba',
                'contact_email' => 'katangalifkat@gmail.com',
                'contact_phone' => '',
            ],
            [
                'name' => 'Ligue de Football du Kongo Central',
                'slug' => Str::slug('Ligue de Football du Kongo Central'),
                'code' => 'LIFKOCESP',
                'province' => 'Kongo Central',
                'headquarters' => 'Matadi',
                'contact_email' => 'lifkocesp@gmail.com',
                'contact_phone' => '+243855115636',
            ],
            [
                'name' => 'Ligue de Football du Kasaï Occidental',
                'slug' => Str::slug('Ligue de Football de Kasaï Occidental'),
                'code' => 'LIFKOC',
                'province' => 'Kasaï-Occidental',
                'headquarters' => 'Kananga',
                'contact_email' => 'lifkoc2025@gmail.com',
                'contact_phone' => '+243976422941',
            ],
            [
                'name' => 'Ligue de Football de la Province Orientale',
                'slug' => Str::slug('Ligue de Football de la Province Orientale'),
                'code' => 'LIFPO',
                'province' => 'Province Orientale',
                'headquarters' => 'Avenue Lac KISALE, C/ Makiso, Kisangani',
                'contact_email' => 'secretariatlifpo@gmail.com',
                'contact_phone' => '+243854002080',
            ],
            [
                'name' => 'Ligue de Football du Sud-Kivu',
                'slug' => Str::slug('Ligue de Football du Sud-Kivu'),
                'code' => 'LIFSKI',
                'province' => 'Sud-Kivu',
                'headquarters' => 'Bukavu',
                'contact_email' => 'lifskibkv@gmail.com',
                'contact_phone' => '',
            ],
            [
                'name' => 'Ligue de Football du Maniema',
                'slug' => Str::slug('Ligue de Football du Maniema'),
                'code' => 'LIFMAN',
                'province' => 'Maniema',
                'headquarters' => '19, Av. de Sport, C/ Kasaku, Ville de Kindu',
                'contact_email' => 'lifmankindu2021@gmail.com',
                'contact_phone' => '+243811740275',
            ],
            [
                'name' => "Ligue de Football de l'Equateur",
                'slug' => Str::slug("Ligue de Football de l'Equateur"),
                'code' => 'LIFEQUA',
                'province' => 'Equateur',
                'headquarters' => 'Parc Joseph KABILA Centre-Ville, Mbandaka',
                'contact_email' => 'lifequa2014@gmail.com',
                'contact_phone' => '+243858118425',
            ],
            [
                'name' => 'Ligue de Football du Kasaï Oriental',
                'slug' => Str::slug('Ligue de Football du Kasaï Oriental'),
                'code' => 'LIFKOR',
                'province' => 'Kasaï Oriental',
                'headquarters' => 'Mbuji-Mayi',
                'contact_email' => 'lifkor2025@gmail.com',
                'contact_phone' => '',
            ],
            [
                'name' => 'Ligue de Football du Bandundu',
                'slug' => Str::slug('Ligue de Football du Bandundu'),
                'code' => 'LIFBAND',
                'province' => 'Bandundu',
                'headquarters' => 'Bandundu-ville',
                'contact_email' => 'zephirinmunzimba@gmail.com',
                'contact_phone' => '',
            ],
            [
                'name' => 'Ligue de Football du Nord-Kivu',
                'slug' => Str::slug('Ligue de Football du Nord-Kivu'),
                'code' => 'LIFNOKI',
                'province' => 'Nord-Kivu',
                'headquarters' => 'Goma',
                'contact_email' => 'lifnokigoma182@gmail.com',
                'contact_phone' => '',
            ],
        ];

        DB::table('leagues')->insert($leagues);
    }
}
