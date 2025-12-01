<?php

namespace App\Models\Referees;

use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefereePhysicalTest extends Model
{
    //
    protected $fillable = [
        'referee_id',
        'test_date',
        'result',
        'level',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'test_date' => 'date',
    ];

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
