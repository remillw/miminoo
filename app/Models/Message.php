<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 
        'sender_id', 
        'message', 
        'type',
        'system_data',
        'read_at'
    ];

    protected $casts = [
        'system_data' => 'array',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Scopes
    public function scopeUserMessages($query)
    {
        return $query->where('type', 'user');
    }

    public function scopeSystemMessages($query)
    {
        return $query->where('type', 'system');
    }

    // Accessors
    public function getIsSystemMessageAttribute()
    {
        return $this->type === 'system';
    }
}