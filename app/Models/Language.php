<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function babysitterProfiles(): BelongsToMany
    {
        return $this->belongsToMany(BabysitterProfile::class, 'babysitter_profile_language')
                    ->withPivot('level')
                    ->withTimestamps();
    }
}
