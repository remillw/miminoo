<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'ad_id', 
        'reservation_id',
        'payer_id', 
        'babysitter_id', 
        'amount', 
        'fee', 
        'payment_method', 
        'stripe_id',
        'stripe_refund_id',
        'status',
        'type',
        'description',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2'
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }

    // Relation user supprimée car la colonne user_id n'existe pas dans la table
    // Utiliser payer() ou babysitter() selon le contexte

    // Scopes pour filtrer par type
    public function scopePayments($query)
    {
        return $query->where('type', 'payment');
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    // Accesseurs pour formater les montants
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' €';
    }

    public function getIsRefundAttribute(): bool
    {
        return $this->type === 'refund';
    }

    public function getIsDeductionAttribute(): bool
    {
        return $this->type === 'deduction';
    }
}