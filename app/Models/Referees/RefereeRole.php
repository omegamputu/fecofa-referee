<?php

namespace App\Models\Referees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefereeRole extends Model
{
    //
    protected $fillable = [
        'name', 'slug', 'description'
    ];

    public function referees(): HasMany
    {
        return $this->hasMany(Referee::class);
    }
}
