<?php

namespace App\Policies;

use App\Models\League;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaguePolicy
{
    
    /**
     * Determine whether the user can view the model.
     */
    public function manage(User $auth): bool
    {
        return $auth->can('manage_leagues');
    }
}
