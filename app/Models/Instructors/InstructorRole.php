<?php

namespace App\Models\Instructors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorRole extends Model
{
    //
    protected $fillable = [
        'name', 'slug', 'description'
    ];

    public function instructors(): HasMany
    {
        return $this->hasMany(Instructor::class);
    }
}
