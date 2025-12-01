<?php

namespace App\Queries;

use App\Models\User;

class UserIndexQuery
{
    public function run(?string $search, int $perPage = 10)
    {
        return User::query()
            ->select(['id','name','email','is_active','invited_at','invitation_sent_count','created_at'])
            ->with('roles:id,name')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Owner'))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy("id","asc")
            ->latest('created_at')
            ->paginate($perPage);
    }
}
