<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;

class WalletFundingService
{
    public function __construct(private readonly WalletService $wallets) {}

    /**
     * @return array{transaction: WalletTransaction, funding_breakdown: array<string, float>, primary_bucket: string|null, balance: array{total: float, real_money: float, promo: float, sale: float, locked: float}}
     */
    public function spend(
        User $user,
        float $amount,
        string $type,
        array $meta = [],
        ?string $idempotencyKey = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $preferredBuckets = null,
        ?array $originContext = null,
    ): array {
        $transaction = $this->wallets->debit(
            user: $user,
            amount: $amount,
            type: $type,
            meta: $meta,
            idempotencyKey: $idempotencyKey,
            referenceType: $referenceType,
            referenceId: $referenceId,
            preferredBuckets: $preferredBuckets,
            originContext: $originContext,
        );

        $fundingBreakdown = data_get($transaction->meta, 'funding_breakdown', []);
        $primaryBucket = array_key_first(array_filter($fundingBreakdown, fn ($value) => (float) $value > 0));

        return [
            'transaction' => $transaction,
            'funding_breakdown' => $fundingBreakdown,
            'primary_bucket' => $primaryBucket,
            'balance' => $this->wallets->spendableBalance($user),
        ];
    }
}
