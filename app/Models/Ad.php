<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Ad extends Model
{
    protected $fillable = [
        'parent_id', 'title', 'description', 'address_id',
        'date_start', 'date_end', 'hourly_rate', 'estimated_duration', 
        'estimated_total', 'children', 'additional_info',
        'status', 'is_boosted', 'guest_email', 'guest_firstname', 'guest_token', 
        'guest_expires_at', 'is_guest'
    ];

    protected $casts = [
        'children' => 'array',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'estimated_duration' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'is_boosted' => 'boolean',
        'is_guest' => 'boolean',
        'guest_expires_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AdApplication::class);
    }

    /**
     * Méthodes pour les annonces guests
     */
    public function isGuest(): bool
    {
        return $this->is_guest;
    }

    public function isExpired(): bool
    {
        return $this->is_guest && $this->guest_expires_at && $this->guest_expires_at->isPast();
    }

    public function getOwnerEmail(): string
    {
        return $this->is_guest ? $this->guest_email : $this->parent->email;
    }

    public function getOwnerName(): string
    {
        if ($this->is_guest) {
            return $this->guest_firstname ?: explode('@', $this->guest_email)[0];
        }
        return $this->parent->firstname . ' ' . $this->parent->lastname;
    }

    /**
     * Associer une annonce guest à un utilisateur existant
     */
    public function associateToUser(User $user): bool
    {
        if (!$this->is_guest || $this->guest_email !== $user->email) {
            return false;
        }

        // Commencer une transaction pour assurer la cohérence
        DB::beginTransaction();

        try {
            // Associer l'annonce à l'utilisateur
            $this->update([
                'parent_id' => $user->id,
                'is_guest' => false,
                'guest_email' => null,
                'guest_token' => null,
                'guest_expires_at' => null,
            ]);

            // Créer les conversations manquantes pour les candidatures existantes
            $applications = $this->applications()->get();
            
            foreach ($applications as $application) {
                // Vérifier si une conversation existe déjà entre le parent et le babysitter
                $existingConversation = \App\Models\Conversation::where('parent_id', $user->id)
                    ->where('babysitter_id', $application->babysitter_id)
                    ->first();

                if (!$existingConversation) {
                    // Créer une nouvelle conversation
                    $conversation = \App\Models\Conversation::create([
                        'parent_id' => $user->id,
                        'babysitter_id' => $application->babysitter_id,
                        'ad_id' => $this->id,
                        'last_message_at' => now(),
                    ]);

                    // Créer un message initial pour expliquer l'association
                    \App\Models\Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => $user->id,
                        'content' => "Bonjour ! Cette conversation a été créée automatiquement suite à votre candidature sur mon annonce. Mon compte a été créé après votre candidature.",
                        'type' => 'text',
                        'is_read' => false,
                    ]);
                }

                // Les babysitters seront notifiés via la messagerie que le parent a créé son compte
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur lors de l\'association de l\'annonce guest', [
                'ad_id' => $this->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Générer un token unique pour l'annonce guest
     */
    public static function generateGuestToken(): string
    {
        do {
            $token = \Illuminate\Support\Str::random(32);
        } while (self::where('guest_token', $token)->exists());

        return $token;
    }
}
