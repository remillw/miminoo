<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdApplication extends Model
{
    protected $fillable = [
        'ad_id', 
        'babysitter_id', 
        'motivation_note', 
        'proposed_rate',
        'counter_rate',
        'counter_message',
        'status',
        'expires_at',
        'accepted_at',
        'viewed_at'
    ];

    protected $casts = [
        'proposed_rate' => 'decimal:2',
        'counter_rate' => 'decimal:2',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'viewed_at' => 'datetime',
    ];

    // Relations
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'application_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->where('status', 'pending');
    }

    // Accessors & Mutators
    public function getEffectiveRateAttribute()
    {
        return $this->proposed_rate;
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getTimeRemainingAttribute()
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return null;
        }
        
        return $this->expires_at->diffForHumans();
    }

    // Methods
    public function markAsViewed()
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }

    /**
     * Réserver la candidature (nouveau système - remplace accept)
     * Cela book l'annonce et lance le processus de paiement
     */
    public function reserve($finalRate = null)
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'counter_rate' => $finalRate
        ]);

        // Archiver automatiquement toutes les autres candidatures de la même annonce
        AdApplication::where('ad_id', $this->ad_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', ['pending', 'counter_offered'])
            ->update(['status' => 'archived']);

        // Archiver les conversations associées aux autres candidatures
        Conversation::whereHas('application', function($query) {
            $query->where('ad_id', $this->ad_id)
                  ->where('id', '!=', $this->id);
        })
        ->where('status', '!=', 'archived')
        ->update(['status' => 'archived']);

        // Mettre à jour l'annonce pour la marquer comme réservée
        $this->ad->update([
            'status' => 'booked',
            'confirmed_application_id' => $this->id
        ]);

        // Mettre à jour la conversation pour la marquer comme paiement requis (pas encore active)
        $this->conversation->update([
            'status' => 'payment_required'
        ]);

        return $this->conversation;
    }

    /**
     * Refuser la candidature - archive la conversation
     */
    public function decline()
    {
        $this->update(['status' => 'declined']);
        
        // Archiver la conversation - ne plus l'afficher dans la liste
        if ($this->conversation) {
            $this->conversation->update(['status' => 'archived']);
        }
    }

    /**
     * Accepter définitivement la candidature au prix proposé par la babysitter
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);

        // Archiver automatiquement toutes les autres candidatures de la même annonce
        AdApplication::where('ad_id', $this->ad_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', ['pending'])
            ->update(['status' => 'declined']);

        // Archiver les conversations associées aux autres candidatures
        Conversation::whereHas('application', function($query) {
            $query->where('ad_id', $this->ad_id)
                  ->where('id', '!=', $this->id);
        })
        ->where('status', '!=', 'archived')
        ->update(['status' => 'archived']);

        return $this->conversation;
    }

    // Boot method pour auto-expiration et création automatique de conversation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            // Expire dans 24h par défaut
            if (!$application->expires_at) {
                $application->expires_at = now()->addHours(24);
            }
        });

        // Créer automatiquement une conversation après création de la candidature
        static::created(function ($application) {
            // Charger la relation ad pour accéder au parent_id
            $application->load('ad');
            
            Conversation::create([
                'ad_id' => $application->ad_id,
                'application_id' => $application->id,
                'parent_id' => $application->ad->parent_id,
                'babysitter_id' => $application->babysitter_id,
                'status' => 'pending' // En attente de décision du parent
            ]);
        });
    }
}