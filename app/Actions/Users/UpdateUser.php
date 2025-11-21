<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateUser
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
    public function __invoke(User $user, array $data, string $role): User
    {
        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles([$role]);

        return $user;
    }
}
