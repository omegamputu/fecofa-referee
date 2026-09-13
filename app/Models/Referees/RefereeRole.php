<?php

namespace App\Models\Referees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefereeRole extends Model
{
    public const CENTRAL_SLUG = 'arbitre';

    public const ASSISTANT_SLUG = 'arbitre-assistant';

    protected $fillable = [
        'name', 'slug', 'description',
    ];

    /**
     * @return array<int, string>
     */
    public static function officiatingSlugs(): array
    {
        return [self::CENTRAL_SLUG, self::ASSISTANT_SLUG];
    }

    public function referees(): HasMany
    {
        return $this->hasMany(Referee::class);
    }
}
