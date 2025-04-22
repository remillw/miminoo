<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdApplication extends Model
{
    protected $fillable = ['ad_id', 'babysitter_id', 'motivation_note', 'status'];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function babysitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'babysitter_id');
    }
}