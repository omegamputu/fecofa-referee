<?php

namespace App\Actions\Users;

use App\Models\User;

class ToggleActive
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Invoke the class instance.
     */
    public function __invoke(User $user): void
    {
        $user->is_active = ! $user->is_active;

        $user->save();
    }
}
