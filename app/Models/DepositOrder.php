<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepositOrder extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_REDIRECTED = 'redirected';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'user_id',
        'reference',
        'gateway',
        'mode',
        'amount_credits',
        'status',
        'external_id',
        'checkout_url',
        'expires_at',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'amount_credits' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(DepositWebhookEvent::class);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_FAILED, self::STATUS_EXPIRED, self::STATUS_CANCELED], true);
    }
}
