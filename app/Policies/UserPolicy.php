<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function manage(User $auth): bool
    {
        return $auth->can('manage_users');
    }

    public function toggle(User $auth, User $user): bool
    {
        return $this->notOwnerAndCanManage($auth, $user);
    }

    public function invite(User $auth, User $user): bool
    {
        return $this->notOwnerAndCanManage($auth, $user);
    }

    public function delete(User $auth, User $user): bool
    {
        return $this->notOwnerAndCanManage($auth, $user);
    }

    protected function notOwnerAndCanManage(User $auth, User $user): bool
    {
        return $auth->can('manage_users') && ! $user->hasRole('Owner');
    }
}
