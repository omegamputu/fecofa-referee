<?php

namespace App\Actions\Leagues;

use App\Models\League;
use Illuminate\Support\Str;

class UpdateLeague
{
    /**
     * Invoke the class instance.
     */
    public function __invoke(League $league, array $data): League
    {
        //
        $league->update([
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name']),
            'code'          => $data['code'] ?? null,
            'province'      => $data['province'] ?? null,
            'headquarters'  => $data['headquarters'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);

        return $league;
    }
}
