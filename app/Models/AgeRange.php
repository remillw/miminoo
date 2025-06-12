<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AgeRange extends Model
{
    protected $fillable = [
        'name',
        'min_age_months',
        'max_age_months',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_age_months' => 'integer',
        'max_age_months' => 'integer',
        'display_order' => 'integer',
    ];

    public function babysitterProfiles(): BelongsToMany
    {
        return $this->belongsToMany(BabysitterProfile::class, 'babysitter_profile_age_range')
                    ->withTimestamps();
    }
}
