<?php

namespace App\Models\Referees;

use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityDocument extends Model
{
    //
    protected $fillable = [
        'referee_id',
        'type',
        'number',
        'issue_date',
        'expiry_date',
        'issuing_country',
        'file_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
