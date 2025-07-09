<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Reservation extends Model
{
    protected $fillable = [
        'ad_id',
        'application_id',
        'conversation_id',
        'parent_id',
        'babysitter_id',
        'hourly_rate',
        'deposit_amount',
        'service_fee',
        'total_deposit',
        'status',
        'funds_status',
        'funds_hold_until',
        'reserved_at',
        'payment_due_at',
        'paid_at',
        'service_start_at',
        'service_end_at',
        'cancelled_at',
        'service_completed_at',
        'funds_released_at',
        'cancellation_reason',
        'cancellation_note',
        'cancellation_penalty',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'stripe_metadata',
        'parent_reviewed',
        'babysitter_reviewed',
        'babysitter_amount',
        'platform_fee',
        'stripe_fee'
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'total_deposit' => 'decimal:2',
        'babysitter_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'stripe_fee' => 'decimal:2',
        'reserved_at' => 'datetime',
        'payment_due_at' => 'datetime',
        'paid_at' => 'datetime',
        'service_start_at' => 'datetime',
        'service_end_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'service_completed_at' => 'datetime',
        'funds_released_at' => 'datetime',
        'funds_hold_until' => 'datetime',
        'cancellation_penalty' => 'boolean',
        'parent_reviewed' => 'boolean',
        'babysitter_reviewed' => 'boolean',
        'stripe_metadata' => 'array'
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function review(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['paid', 'active']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->whereIn('status', ['cancelled_by_parent', 'cancelled_by_babysitter']);
    }

    public function scopeForParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeForBabysitter($query, $babysitterId)
    {
        return $query->where('babysitter_id', $babysitterId);
    }

    public function scopePaymentOverdue($query)
    {
        return $query->where('status', 'pending_payment')
                    ->where('payment_due_at', '<', now());
    }

    public function scopeCanBeCancelled($query)
    {
        return $query->whereIn('status', ['pending_payment', 'paid'])
                    ->where('service_start_at', '>', now()->addHours(24));
    }

    // Accessors
    public function getCanBeCancelledFreeAttribute(): bool
    {
        if (!in_array($this->status, ['pending_payment', 'paid'])) {
            return false;
        }

        // Annulation gratuite si plus de 24h avant le début
        return $this->service_start_at && $this->service_start_at->gt(now()->addHours(24));
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->status, ['pending_payment', 'paid']) && 
               $this->service_start_at && 
               $this->service_start_at->gt(now());
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['paid', 'active']);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsCancelledAttribute(): bool
    {
        return in_array($this->status, ['cancelled_by_parent', 'cancelled_by_babysitter']);
    }

    public function getTimeUntilServiceAttribute(): ?string
    {
        if (!$this->service_start_at) return null;

        $diff = now()->diffInHours($this->service_start_at, false);
        
        if ($diff < 0) return 'Commencé';
        if ($diff < 24) return $diff . 'h';
        
        $days = floor($diff / 24);
        return $days . 'j';
    }

    public function getCanBeReviewedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'service_completed']) && 
               $this->service_end_at && 
               $this->service_end_at->lt(now());
    }

    public function getCanBeReviewedByParentAttribute(): bool
    {
        return $this->can_be_reviewed && !$this->parent_reviewed;
    }

    public function getCanBeReviewedByBabysitterAttribute(): bool
    {
        return $this->can_be_reviewed && !$this->babysitter_reviewed;
    }

    // Methods
    public static function createFromApplication(AdApplication $application, float $finalRate = null): self
    {
        $rate = $finalRate ?? $application->effective_rate;
        $depositAmount = $rate; // 1 heure d'acompte
        $serviceFee = 2.00; // Frais fixes
        $totalDeposit = $depositAmount + $serviceFee;

        return self::create([
            'ad_id' => $application->ad_id,
            'application_id' => $application->id,
            'conversation_id' => $application->conversation?->id,
            'parent_id' => $application->ad->parent_id,
            'babysitter_id' => $application->babysitter_id,
            'hourly_rate' => $rate,
            'deposit_amount' => $depositAmount,
            'service_fee' => $serviceFee,
            'total_deposit' => $totalDeposit,
            'status' => 'pending_payment',
            'reserved_at' => now(),
            'payment_due_at' => now()->addHours(24), // 24h pour payer
            'service_start_at' => $application->ad->date_start,
            'service_end_at' => $application->ad->date_end,
        ]);
    }

    public function markAsPaid(string $paymentIntentId): bool
    {
        // Calculer et stocker les montants lors du paiement
        $babysitterAmount = $this->getBabysitterAmountAttribute();
        $platformFee = $this->getPlatformFeeAttribute();
        $stripeFee = $this->getStripeFeeAttribute();

        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_payment_intent_id' => $paymentIntentId,
            'funds_status' => 'pending_service',
            'babysitter_amount' => $babysitterAmount,
            'platform_fee' => $platformFee,
            'stripe_fee' => $stripeFee
        ]);
    }

    public function startService(): bool
    {
        if ($this->status !== 'paid') return false;

        return $this->update([
            'status' => 'active',
            'service_start_at' => now()
        ]);
    }

    public function completeService(): bool
    {
        // Accepter 'paid' (service pas commencé) et 'active' (service en cours)
        if (!in_array($this->status, ['paid', 'active'])) return false;

        // Utiliser la vraie date de fin du service (service_end_at) pour calculer la libération
        $serviceEndDate = $this->service_end_at ? 
            \Carbon\Carbon::parse($this->service_end_at) : 
            now();

        return $this->update([
            'status' => 'service_completed',
            'service_completed_at' => now(),
            'service_end_at' => $serviceEndDate, // Garder la date de fin prévue
            'funds_status' => 'held_for_validation',
            'funds_hold_until' => $serviceEndDate->addHours(24) // 24h après la FIN du service
        ]);
    }

    public function cancelByParent(string $reason = null, string $note = null): bool
    {
        $penalty = !$this->can_be_cancelled_free;

        return $this->update([
            'status' => 'cancelled_by_parent',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancellation_note' => $note,
            'cancellation_penalty' => $penalty
        ]);
    }

    public function cancelByBabysitter(string $reason = null, string $note = null): bool
    {
        return $this->update([
            'status' => 'cancelled_by_babysitter',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancellation_note' => $note,
            'cancellation_penalty' => false // Pas de pénalité financière pour la babysitter
        ]);
    }

    public function getRefundAmount(): float
    {
        // Pas de remboursement si annulation tardive par le parent avec pénalité
        if ($this->status === 'cancelled_by_parent' && $this->cancellation_penalty) {
            return 0;
        }

        // Si babysitter annule ou parent annule sans pénalité
        return $this->getParentRefundAmount();
    }

    /**
     * Calculer le montant remboursé au parent
     * Parent reçoit : Acompte babysitter - frais Stripe de remboursement
     * Les frais de service (2€) restent acquis à la plateforme
     * Les frais Stripe sont à la charge du parent lors du remboursement
     */
    public function getParentRefundAmount(): float
    {
        $baseRefundAmount = $this->deposit_amount; // Acompte babysitter (17€)
        $stripeRefundFees = $this->getStripeRefundFees(); // Frais Stripe pour le remboursement (0,87€)
        
        $refundAmount = $baseRefundAmount - $stripeRefundFees; // 17€ - 0,87€ = 16,13€
        
        // S'assurer que le montant n'est pas négatif
        return max(0, round($refundAmount, 2));
    }

    /**
     * Calculer les frais Stripe pour un remboursement
     * Les frais correspondent aux frais de paiement originaux que Stripe ne rembourse pas
     * Formula : 2.9% + 0.25€ du montant total payé
     */
    public function getStripeRefundFees(): float
    {
        // Frais de paiement Stripe originaux (que Stripe garde lors du remboursement)
        // Ces frais sont à la charge du parent lors du remboursement
        $stripePaymentFees = ($this->total_deposit * 0.029) + 0.25;
        
        return round($stripePaymentFees, 2);
    }

    /**
     * Calculer le montant à déduire du compte babysitter
     * La babysitter perd TOUJOURS l'acompte complet (11€) peu importe les frais
     * Le parent reçoit moins à cause des frais, mais la babysitter perd le montant plein
     */
    public function getBabysitterDeductionAmount(): float
    {
        // Si c'est la babysitter qui annule, elle perd tous les fonds qu'elle aurait reçus
        if ($this->status === 'cancelled_by_babysitter') {
            return $this->babysitter_amount;
        }

        // Si c'est le parent qui annule et demande un remboursement
        if ($this->status === 'cancelled_by_parent' && !$this->cancellation_penalty) {
            // La babysitter perd TOUJOURS l'acompte complet (deposit_amount = 11€)
            // Peu importe ce que le parent récupère après déduction des frais
            return $this->deposit_amount; // 11€ dans tous les cas
        }

        return 0;
    }

    public function shouldReceiveBadReview(): bool
    {
        return $this->status === 'cancelled_by_babysitter' && 
               !$this->can_be_cancelled_free;
    }

    /**
     * Calculer les frais Stripe (2.9% + 0.25€) basés sur le montant total
     */
    public function getStripeFeeAttribute(): float
    {
        return round(($this->total_deposit * 0.029) + 0.25, 2);
    }

    /**
     * Application fee pour la plateforme (seulement les frais de service)
     */
    public function getPlatformFeeAttribute(): float
    {
        return $this->service_fee;
    }

    /**
     * Montant que recevra la babysitter (montant total - frais plateforme - frais Stripe)
     */
    public function getBabysitterAmountAttribute(): float
    {
        // La babysitter reçoit : total payé - frais de service - frais Stripe
        return round($this->total_deposit - $this->service_fee - $this->stripe_fee, 2);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($reservation) {
            // Mettre à jour le statut de la conversation
            if ($reservation->conversation) {
                $reservation->conversation->update([
                    'status' => 'payment_required'
                ]);
            }

            // Mettre à jour le statut de l'application
            if ($reservation->application) {
                $reservation->application->update([
                    'status' => 'accepted'
                ]);
            }
        });

        static::updated(function ($reservation) {
            // Mettre à jour le statut de la conversation selon le statut de la réservation
            if ($reservation->conversation) {
                $conversationStatus = match($reservation->status) {
                    'pending_payment' => 'payment_required',
                    'paid', 'active' => 'active',
                    'completed' => 'completed',
                    'cancelled_by_parent', 'cancelled_by_babysitter' => 'cancelled',
                    default => 'active'
                };

                $reservation->conversation->update([
                    'status' => $conversationStatus
                ]);
            }
        });
    }
}
