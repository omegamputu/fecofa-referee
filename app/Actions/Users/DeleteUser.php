<?php

namespace App\Actions\Users;

use App\Models\User;

class DeleteUser
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
        if ($user->hasRole('Owner')) {
            abort(403, "You can not delete Owner");
        }

        $user->delete();
    }
}
