<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    protected $fillable = [
        'parent_id', 'title', 'description', 'address_id',
        'date_start', 'date_end', 'hourly_rate', 'estimated_duration', 
        'estimated_total', 'children', 'additional_info',
        'status', 'is_boosted'
    ];

    protected $casts = [
        'children' => 'array',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'estimated_duration' => 'decimal:2',
        'estimated_total' => 'decimal:2',
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

    // Plus besoin des accesseurs, on utilise maintenant les colonnes dédiées
}
