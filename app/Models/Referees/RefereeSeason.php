<?php

namespace App\Models\Referees;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefereeSeason extends Model
{
    public const COMPETITION_LIGUE_1 = 'ligue_1';

    public const COMPETITION_LIGUE_2 = 'ligue_2';

    protected $fillable = [
        'referee_id',
        'season_year',
        'competition',
        'status',
        'list_type',
        'comment',
        'designated_by',
        'designated_at',
    ];

    protected $casts = [
        'season_year' => 'integer',
        'designated_at' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function competitions(): array
    {
        return [self::COMPETITION_LIGUE_1, self::COMPETITION_LIGUE_2];
    }

    public static function competitionLabel(string $competition): string
    {
        return match ($competition) {
            self::COMPETITION_LIGUE_1 => 'Ligue 1',
            self::COMPETITION_LIGUE_2 => 'Ligue 2',
            default => $competition,
        };
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }

    public function designatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designated_by');
    }
}
