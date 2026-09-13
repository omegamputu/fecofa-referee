<?php

namespace App\Models\Referees;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefereeMedicalExam extends Model
{
    public const RESULT_PASSED = 'passed';

    public const RESULT_FAILED = 'failed';

    public const RESULT_PENDING = 'pending';

    protected $fillable = [
        'referee_id',
        'season_year',
        'exam_date',
        'result',
        'file_path',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'season_year' => 'integer',
    ];

    /**
     * @return array<int, string>
     */
    public static function results(): array
    {
        return [self::RESULT_PASSED, self::RESULT_FAILED, self::RESULT_PENDING];
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
