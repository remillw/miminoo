<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    protected $fillable = [
        'parent_id', 'title', 'description', 'address_id',
        'date_start', 'date_end', 'additional_data',
        'status', 'is_boosted'
    ];

    protected $casts = [
        'additional_data' => 'array',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'is_boosted' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AdApplication::class);
    }

    // Accesseurs pour les données JSON
    public function getChildrenAttribute()
    {
        return $this->additional_data['children'] ?? [];
    }

    public function getHourlyRateAttribute()
    {
        return $this->additional_data['hourly_rate'] ?? 0;
    }

    public function getEstimatedDurationAttribute()
    {
        return $this->additional_data['estimated_duration'] ?? 0;
    }

    public function getEstimatedTotalAttribute()
    {
        return $this->additional_data['estimated_total'] ?? 0;
    }
}
