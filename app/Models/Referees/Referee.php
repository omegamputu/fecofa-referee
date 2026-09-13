<?php

namespace App\Models\Referees;

use App\Models\League;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Referee extends Model
{
    protected $fillable = [
        'league_id',
        'referee_category_id',
        'referee_role_id',
        'person_id',
        'last_name',
        'first_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'education_level',
        'profession',
        'start_year',
        'category',
        'has_medical_clearance',
        'has_physical_clearance',
        'is_active',
        'is_fifa_listed',
        'profile_photo_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'has_medical_clearance' => 'boolean',
        'has_physical_clearance' => 'boolean',
        'is_active' => 'boolean',
        'is_fifa_listed' => 'boolean',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function refereeCategory(): BelongsTo
    {
        return $this->belongsTo(RefereeCategory::class, 'referee_category_id');
    }

    public function refereeRole(): BelongsTo
    {
        return $this->belongsTo(RefereeRole::class, 'referee_role_id');
    }

    public function identityDocument(): HasOne
    {
        return $this->hasOne(IdentityDocument::class);
    }

    public function medicalExams(): HasMany
    {
        return $this->hasMany(RefereeMedicalExam::class);
    }

    public function physicalTests(): HasMany
    {
        return $this->hasMany(RefereePhysicalTest::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(RefereeSeason::class);
    }

    public function scopeEligibleForSeason(Builder $query, ?int $seasonYear = null): Builder
    {
        $query
            ->where('is_active', true)
            ->whereHas('refereeRole', fn (Builder $roleQuery) => $roleQuery
                ->whereIn('slug', RefereeRole::officiatingSlugs()));

        if ($seasonYear === null) {
            return $query
                ->where('has_medical_clearance', true)
                ->where('has_physical_clearance', true);
        }

        return $query
            ->whereHas('medicalExams', fn (Builder $examQuery) => $examQuery
                ->where('season_year', $seasonYear)
                ->where('result', RefereeMedicalExam::RESULT_PASSED))
            ->whereHas('physicalTests', fn (Builder $testQuery) => $testQuery
                ->where('season_year', $seasonYear)
                ->where('result', RefereePhysicalTest::RESULT_PASSED));
    }

    public function isEligibleForSeason(?int $seasonYear = null): bool
    {
        if (! $this->is_active
            || ! in_array($this->refereeRole?->slug, RefereeRole::officiatingSlugs(), true)) {
            return false;
        }

        if ($seasonYear === null) {
            return $this->has_medical_clearance && $this->has_physical_clearance;
        }

        return $this->medicalExams()
            ->where('season_year', $seasonYear)
            ->where('result', RefereeMedicalExam::RESULT_PASSED)
            ->exists()
            && $this->physicalTests()
                ->where('season_year', $seasonYear)
                ->where('result', RefereePhysicalTest::RESULT_PASSED)
                ->exists();
    }

    public function fullName(): string
    {
        return "{$this->last_name} {$this->first_name}";
    }
}
