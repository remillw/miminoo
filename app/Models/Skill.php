<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function babysitterProfiles(): BelongsToMany
    {
        return $this->belongsToMany(BabysitterProfile::class, 'babysitter_profile_skill')
                    ->withTimestamps();
    }
}
