<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;

class ResendInvite
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
            abort(403, 'You can not resend invitation to Owner');
        }

        Cache::put("invite:{$user->email}", true, now()->addMinutes(2));

        Password::broker('invites')->sendResetLink(['email' => $user->email]);

        $user->forceFill([
            'invited_at' => now(),
        ])->save();

        $user->increment('invitation_sent_count');
    }
}
