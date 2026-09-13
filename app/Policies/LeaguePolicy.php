<?php

namespace App\Policies;

use App\Models\User;

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
