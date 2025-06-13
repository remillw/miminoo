<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BabysitterProfile extends Model
{
    protected $fillable = [
        'user_id', 'bio', 'experience_years', 'available_radius_km',
        'hourly_rate', 'documents_verified', 'is_available', 
        'has_driving_license', 'has_vehicle', 'comfortable_with_all_ages', 'verification_status', 'rejection_reason', 'verified_at', 'verified_by'
    ];

    protected $casts = [
        'documents_verified' => 'boolean',
        'is_available' => 'boolean',
        'has_driving_license' => 'boolean',
        'has_vehicle' => 'boolean',
        'comfortable_with_all_ages' => 'boolean',
        'hourly_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'babysitter_profile_language')
                    ->withPivot('level')
                    ->withTimestamps();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'babysitter_profile_skill')
                    ->withTimestamps();
    }

    public function excludedAgeRanges(): BelongsToMany
    {
        return $this->belongsToMany(AgeRange::class, 'babysitter_profile_excluded_age_range')
                    ->withTimestamps();
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(BabysitterExperience::class);
    }

    public function formations(): HasMany
    {
        return $this->hasMany(BabysitterExperience::class)->where('type', 'formation');
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(BabysitterExperience::class)->where('type', 'experience');
    }
}