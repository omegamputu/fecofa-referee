<?php

namespace App\Models\Referees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefereeSeason extends Model
{
    //
    protected $fillable = [
        'referee_id',
        'season_year',
        'status',
        'list_type',
        'comment',
    ];

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
