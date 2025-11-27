<?php

namespace App\Models\Referees;

use App\Models\League;
use App\Models\Referee\IdentityDocument;
use App\Models\Referee\RefereeMedicalExam;
use App\Models\Referee\RefereePhysicalTest;
use App\Models\Referee\RefereeSeason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referee extends Model
{
    //
    protected $fillable = [
        'referee_role_id',
        'code',
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
        'is_active',
        'is_fifa_listed',
        'profile_photo_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'is_fifa_listed' => 'boolean',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(RefereeRole::class, 'referee_role_id');
    }

    public function identityDocuments(): HasMany
    {
        return $this->hasMany(IdentityDocument::class);
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

    // Petit helper pratique
    public function fullName(): string
    {
        return "{$this->last_name} {$this->first_name}";
    }
}
