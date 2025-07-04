<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email', 
        'phone',
        'subject',
        'message',
        'status',
        'read_at',
        'admin_notes'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the subject text in French
     */
    public function getSubjectTextAttribute(): string
    {
        $subjects = [
            'recherche' => 'Recherche de babysitter',
            'inscription' => 'Connexion/Inscription',
            'tarifs' => 'Tarifs',
            'technique' => 'Problème technique',
            'amélioration' => 'Suggestion d\'amélioration',
            'autre' => 'Autre'
        ];

        return $subjects[$this->subject] ?? $this->subject;
    }

    /**
     * Get the status text in French
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'unread' => 'Non lu',
            'read' => 'Lu',
            'replied' => 'Répondu'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    /**
     * Mark as replied
     */
    public function markAsReplied(): void
    {
        $this->update([
            'status' => 'replied'
        ]);
    }

    /**
     * Scope for unread contacts
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope for recent contacts (last 30 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }
}
