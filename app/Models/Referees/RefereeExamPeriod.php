<?php

namespace App\Models\Referees;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefereeExamPeriod extends Model
{
    protected $fillable = [
        'season_year',
        'opens_at',
        'closes_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'season_year' => 'integer',
        'opens_at' => 'date',
        'closes_at' => 'date',
    ];

    public function scopeOpen(Builder $query, ?CarbonInterface $date = null): Builder
    {
        $date ??= today();

        return $query
            ->whereDate('opens_at', '<=', $date)
            ->whereDate('closes_at', '>=', $date);
    }

    public function isOpen(?CarbonInterface $date = null): bool
    {
        $date ??= today();

        return $date->betweenIncluded($this->opens_at, $this->closes_at);
    }

    public function seasonLabel(): string
    {
        return $this->season_year.'–'.($this->season_year + 1);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
