<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    //
    protected $fillable = [
        'name',
        'slug',
        'code',
        'province',
        'headquarters',
        'contact_email',
        'contact_phone',
    ];
}
