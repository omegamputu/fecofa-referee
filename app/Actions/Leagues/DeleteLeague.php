<?php

namespace App\Actions\Leagues;

use App\Models\League;

class DeleteLeague
{

    /**
     * Invoke the class instance.
     */
    public function __invoke(League $league): void
    {
        //
        $league->delete();
    }
}
