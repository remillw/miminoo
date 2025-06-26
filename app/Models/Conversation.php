<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'ad_id',
        'application_id', 
        'parent_id',
        'babysitter_id',
        'status',
        'deposit_paid',
        'service_started_at',
        'service_completed_at',
        'last_message_at',
        'last_message_by'
    ];

    protected $casts = [
        'deposit_paid' => 'boolean',
        'service_started_at' => 'datetime',
        'service_completed_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    // Relations
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdApplication::class, 'application_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }

    public function lastMessageBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_message_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'payment_required', 'active']);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('status', '!=', 'archived');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('parent_id', $userId)
              ->orWhere('babysitter_id', $userId);
        })->notArchived();
    }

    public function scopeWithUnreadMessages($query, $userId)
    {
        return $query->whereHas('messages', function ($q) use ($userId) {
            $q->where('sender_id', '!=', $userId)
              ->whereNull('read_at');
        });
    }

    // Accessors
    public function getOtherUserAttribute()
    {
        $currentUserId = auth()->id();
        return $this->parent_id === $currentUserId ? $this->babysitter : $this->parent;
    }

    public function getUnreadCountAttribute()
    {
        $currentUserId = auth()->id();
        return $this->messages()
            ->where('sender_id', '!=', $currentUserId)
            ->whereNull('read_at')
            ->count();
    }

    public function getDepositAmountAttribute()
    {
        $rate = $this->application->effective_rate;
        return $rate; // 1 heure d'accompte
    }

    public function getServiceFeeAttribute()
    {
        return 2.00; // Frais fixes de 2€
    }

    public function getTotalDepositAttribute()
    {
        return $this->deposit_amount + $this->service_fee;
    }

    // Methods
    public function getOtherUser($currentUserId = null)
    {
        $currentUserId = $currentUserId ?? auth()->id();
        return $this->parent_id === $currentUserId ? $this->babysitter : $this->parent;
    }

    public function markMessagesAsRead($userId)
    {
        \Log::info('=== MARQUAGE MESSAGES COMME LUS ===', [
            'conversation_id' => $this->id,
            'user_id' => $userId,
        ]);
        
        $updatedCount = $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        \Log::info('Messages marqués comme lus', [
            'updated_count' => $updatedCount
        ]);
            
        // Émettre l'événement seulement si des messages ont été marqués comme lus
        if ($updatedCount > 0) {
            $user = User::find($userId);
            if ($user) {
                \Log::info('Émission événement MessageRead', [
                    'conversation_id' => $this->id,
                    'read_by' => $user->id
                ]);
                event(new \App\Events\MessageRead($this, $user));
            }
        }
    }

    public function addSystemMessage($type, $data = [])
    {
        return $this->messages()->create([
            'sender_id' => null, // Message système
            'message' => $this->getSystemMessageText($type, $data),
            'type' => 'system',
            'system_data' => $data,
            'read_at' => now() // Messages système marqués comme lus
        ]);
    }

    public function startService()
    {
        $this->update(['service_started_at' => now()]);
        $this->addSystemMessage('service_started');
    }

    public function completeService()
    {
        $this->update([
            'service_completed_at' => now(),
            'status' => 'completed'
        ]);
        $this->addSystemMessage('service_completed');
    }

    private function getSystemMessageText($type, $data = [])
    {
        switch ($type) {
            case 'conversation_started':
                return "La conversation a commencé ! Vous pouvez maintenant discuter des détails du babysitting.";
            case 'deposit_paid':
                return "L'accompte de {$data['amount']}€ a été payé avec succès.";
            case 'service_started':
                return "Le service de babysitting a commencé.";
            case 'service_completed':
                return "Le service de babysitting est terminé. N'hésitez pas à laisser un avis !";
            case 'conversation_archived':
                return "Cette conversation a été archivée par {$data['archived_by_name']}.";
            default:
                return "Mise à jour du statut de la conversation.";
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($conversation) {
            // Ajouter le message de candidature comme premier message si une application existe
            if ($conversation->application_id) {
                // Charger l'application avec les relations nécessaires
                $application = \App\Models\AdApplication::with('babysitter')->find($conversation->application_id);
                
                if ($application && $application->babysitter) {
                    // Utiliser le vrai message de motivation de la babysitter
                    $candidatureMessage = $application->motivation_note;
                    
                    // Ajouter le tarif proposé si différent du tarif demandé
                    if ($application->proposed_rate) {
                        $candidatureMessage .= "\n\nTarif proposé : {$application->proposed_rate}€/h";
                    }
                    
                    // Créer le message seulement s'il y a un message de motivation
                    if ($candidatureMessage && trim($candidatureMessage)) {
                        $conversation->messages()->create([
                            'sender_id' => $application->babysitter_id,
                            'message' => $candidatureMessage,
                            'type' => 'user', // Type normal, pas système
                            'read_at' => null // Non lu par défaut
                        ]);
                    }
                }
            }
        });
    }
}