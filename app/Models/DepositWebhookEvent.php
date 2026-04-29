<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositWebhookEvent extends Model
{
    protected $fillable = [
        'gateway',
        'event_id',
        'external_id',
        'deposit_order_id',
        'signature_header',
        'is_signature_valid',
        'is_processed',
        'payload',
    ];

    protected $casts = [
        'is_signature_valid' => 'boolean',
        'is_processed' => 'boolean',
        'payload' => 'array',
    ];

    public function depositOrder(): BelongsTo
    {
        return $this->belongsTo(DepositOrder::class);
    }
}
