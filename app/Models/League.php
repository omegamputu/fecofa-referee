<?php

namespace App\Models;

use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    //
    protected $fillable = [
        'name',
        'slug',
        'code',
        'province',
        'headquarters',
        'contact_email',
        'contact_phone',
    ];

    public function referees(): HasMany
    {
        return $this->hasMany(Referee::class);
    }
}
