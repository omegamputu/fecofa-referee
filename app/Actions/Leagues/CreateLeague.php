<?php

namespace App\Actions\Leagues;

use App\Models\League;
use Illuminate\Support\Str;

class CreateLeague
{

    /**
     * Invoke the class instance.
     */
    public function __invoke(array $data): League
    {
        //
        return League::create([
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name']),
            'code'          => $data['code'] ?? null,
            'province'      => $data['province'] ?? null,
            'headquarters'  => $data['headquarters'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);
    }
}
