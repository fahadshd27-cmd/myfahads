<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance_credits',
        'real_money_credits',
        'promo_credits',
        'sale_credits',
        'locked_credits',
    ];

    protected $casts = [
        'balance_credits' => 'decimal:2',
        'real_money_credits' => 'decimal:2',
        'promo_credits' => 'decimal:2',
        'sale_credits' => 'decimal:2',
        'locked_credits' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * @return array{total: float, real_money: float, promo: float, sale: float, locked: float}
     */
    public function balanceSummary(): array
    {
        return [
            'total' => (float) $this->balance_credits,
            'real_money' => (float) $this->real_money_credits,
            'promo' => (float) $this->promo_credits,
            'sale' => (float) $this->sale_credits,
            'locked' => (float) $this->locked_credits,
        ];
    }
}
