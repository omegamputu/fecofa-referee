<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class CreateUser
{
    /**
     * Invoke the class instance.
     */
    public function __invoke(array $data, string $role): User
    {
        //
        return DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => bcrypt(str()->random(12)),
            ]);

            $user->assignRole($role);

            // Envoi d’invitation via broker "invites"
            Password::broker('invites')->sendResetLink(['email' => $user->email]);

            $user->forceFill([
                'invited_at' => now(),
            ])->save();

            $user->increment('invitation_sent_count');

            return $user;
        });
    }
}
