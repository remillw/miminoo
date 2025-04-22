<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'ad_id', 'payer_id', 'babysitter_id', 'amount', 'fee', 'payment_method', 'stripe_id', 'status'
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }
}