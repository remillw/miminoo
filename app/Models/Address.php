<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    protected $fillable = [
        'address',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'google_place_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
