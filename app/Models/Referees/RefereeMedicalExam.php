<?php

namespace App\Models\Referees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefereeMedicalExam extends Model
{
    //
    protected $fillable = [
        'referee_id',
        'exam_date',
        'result',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
