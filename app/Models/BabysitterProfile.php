<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BabysitterProfile extends Model
{
    protected $fillable = [
        'user_id', 'bio', 'experience_years', 'available_radius_km',
        'availability', 'languages', 'documents_verified'
    ];

    protected $casts = [
        'availability' => 'array',
        'languages' => 'array',
        'documents_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}