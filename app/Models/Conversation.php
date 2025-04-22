<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['ad_id', 'parent_id', 'babysitter_id'];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}