<?php

namespace App\Models\Instructors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instructor extends Model
{
    //
    protected $fillable = [
        'instructor_role_id',
        'referee_category_id',
        'referee_role_id',
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
        'is_active',
        'profile_photo_path',
    ];

    public function roleName(): BelongsTo
    {
        return $this->belongsTo(InstructorRole::class);
    }
}
