<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        if (! $user->is_active) {
            $user->setRememberToken(Str::random(60));
        }

        $user->save();

        if (! $user->is_active && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
