<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BabysitterExperience extends Model
{
    protected $fillable = [
        'babysitter_profile_id',
        'type',
        'title',
        'description',
        'institution',
        'start_date',
        'end_date',
        'is_current'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function babysitterProfile(): BelongsTo
    {
        return $this->belongsTo(BabysitterProfile::class);
    }
}
