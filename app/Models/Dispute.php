<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = [
        'reservation_id',
        'reporter_id',
        'reported_id',
        'reason',
        'description',
        'status',
        'admin_response',
        'resolved_at',
        'resolved_by'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // Relations
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    // Methods
    public function resolve(string $response, User $admin): bool
    {
        return $this->update([
            'status' => 'resolved',
            'admin_response' => $response,
            'resolved_at' => now(),
            'resolved_by' => $admin->id
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'in_progress' => 'En cours',
            'resolved' => 'Résolue',
            'rejected' => 'Rejetée',
            default => 'Inconnue'
        };
    }
} 