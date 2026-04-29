<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'credit_source',
        'bucket',
        'amount',
        'balance_before',
        'balance_after',
        'funding_bucket_before',
        'funding_bucket_after',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'origin_context',
        'created_by_admin_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'funding_bucket_before' => 'array',
        'funding_bucket_after' => 'array',
        'origin_context' => 'array',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }
}
