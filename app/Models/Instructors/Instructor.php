<?php

namespace App\Models\Instructors;

use App\Models\Referees\IdentityDocument;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Instructor extends Model
{
    //
    protected $fillable = [
        'instructor_role_id',
        'referee_category_id',
        'referee_role_id',
        'last_name',
        'first_name',
        'year_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'education_level',
        'profession',
        'start_year',
        'is_active',
        'profile_photo_path',
    ];

    public function instructorRole(): BelongsTo
    {
        return $this->belongsTo(InstructorRole::class);
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
}
