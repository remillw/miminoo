<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    protected $fillable = [
        'parent_id', 'title', 'description', 'address', 'latitude', 'longitude',
        'date_start', 'date_end', 'status', 'is_boosted'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AdApplication::class);
    }
}
