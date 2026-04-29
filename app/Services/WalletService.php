<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public const BUCKET_REAL_MONEY = 'real_money';

    public const BUCKET_PROMO = 'promo';

    public const BUCKET_SALE = 'sale';

    /**
     * @return array<int, string>
     */
    public static function availableBuckets(): array
    {
        return [
            self::BUCKET_PROMO,
            self::BUCKET_SALE,
            self::BUCKET_REAL_MONEY,
        ];
    }

    public function ensureWallet(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(['user_id' => $user->id], [
            'balance_credits' => 0,
            'real_money_credits' => 0,
            'promo_credits' => 0,
            'sale_credits' => 0,
            'locked_credits' => 0,
        ]);
    }

    public function credit(
        User $user,
        float $amount,
        string $type,
        array $meta = [],
        ?string $idempotencyKey = null,
        ?User $admin = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $bucket = self::BUCKET_PROMO,
        ?string $creditSource = null,
        ?array $originContext = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Credit amount must be positive.']);
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta, $idempotencyKey, $admin, $referenceType, $referenceId, $bucket, $creditSource, $originContext) {
            $wallet = Wallet::query()
                ->whereKey($this->ensureWallet($user)->id)
                ->lockForUpdate()
                ->firstOrFail();

            $beforeBuckets = $this->bucketSnapshot($wallet);
            $column = $this->bucketColumn($bucket);

            $wallet->{$column} = (float) $wallet->{$column} + $amount;
            $wallet->balance_credits = $this->sumBalance($wallet);
            $wallet->save();

            $afterBuckets = $this->bucketSnapshot($wallet);

            return WalletTransaction::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'credit_source' => $creditSource ?? $bucket,
                'bucket' => $bucket,
                'amount' => $amount,
                'balance_before' => array_sum($beforeBuckets),
                'balance_after' => array_sum($afterBuckets),
                'funding_bucket_before' => $beforeBuckets,
                'funding_bucket_after' => $afterBuckets,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'origin_context' => $originContext,
                'created_by_admin_id' => $admin?->id,
                'meta' => $meta,
            ]);
        });
    }

    public function debit(
        User $user,
        float $amount,
        string $type,
        array $meta = [],
        ?string $idempotencyKey = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $preferredBuckets = null,
        ?array $originContext = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Debit amount must be positive.']);
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta, $idempotencyKey, $referenceType, $referenceId, $preferredBuckets, $originContext) {
            $wallet = Wallet::query()
                ->whereKey($this->ensureWallet($user)->id)
                ->lockForUpdate()
                ->firstOrFail();

            $beforeBuckets = $this->bucketSnapshot($wallet);
            if (array_sum($beforeBuckets) < $amount) {
                throw ValidationException::withMessages(['balance' => 'Insufficient balance.']);
            }

            $fundingBreakdown = $this->allocateDebit($wallet, $amount, $preferredBuckets);
            $wallet->balance_credits = $this->sumBalance($wallet);
            $wallet->save();

            $afterBuckets = $this->bucketSnapshot($wallet);

            return WalletTransaction::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'credit_source' => array_key_first(array_filter($fundingBreakdown, fn (float $value) => $value > 0)) ?: null,
                'bucket' => 'mixed',
                'amount' => -$amount,
                'balance_before' => array_sum($beforeBuckets),
                'balance_after' => array_sum($afterBuckets),
                'funding_bucket_before' => $beforeBuckets,
                'funding_bucket_after' => $afterBuckets,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'origin_context' => $originContext,
                'meta' => array_merge($meta, [
                    'funding_breakdown' => $fundingBreakdown,
                ]),
            ]);
        });
    }

    /**
     * @return array{real_money: float, promo: float, sale: float}
     */
    public function bucketSnapshot(Wallet $wallet): array
    {
        return [
            self::BUCKET_REAL_MONEY => (float) $wallet->real_money_credits,
            self::BUCKET_PROMO => (float) $wallet->promo_credits,
            self::BUCKET_SALE => (float) $wallet->sale_credits,
        ];
    }

    /**
     * @return array{total: float, real_money: float, promo: float, sale: float, locked: float}
     */
    public function spendableBalance(User $user): array
    {
        return $this->ensureWallet($user)->fresh()->balanceSummary();
    }

    /**
     * @param  array<int, string>|null  $preferredBuckets
     * @return array<string, float>
     */
    private function allocateDebit(Wallet $wallet, float $amount, ?array $preferredBuckets = null): array
    {
        $remaining = $amount;
        $breakdown = [
            self::BUCKET_PROMO => 0.0,
            self::BUCKET_SALE => 0.0,
            self::BUCKET_REAL_MONEY => 0.0,
        ];

        foreach ($preferredBuckets ?: self::availableBuckets() as $bucket) {
            if ($remaining <= 0) {
                break;
            }

            $column = $this->bucketColumn($bucket);
            $available = (float) $wallet->{$column};
            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);
            $wallet->{$column} = $available - $take;
            $breakdown[$bucket] += $take;
            $remaining -= $take;
        }

        if ($remaining > 0.00001) {
            throw ValidationException::withMessages(['balance' => 'Insufficient balance.']);
        }

        return $breakdown;
    }

    private function sumBalance(Wallet $wallet): float
    {
        return (float) $wallet->real_money_credits + (float) $wallet->promo_credits + (float) $wallet->sale_credits;
    }

    private function bucketColumn(string $bucket): string
    {
        return match ($bucket) {
            self::BUCKET_REAL_MONEY => 'real_money_credits',
            self::BUCKET_PROMO => 'promo_credits',
            self::BUCKET_SALE => 'sale_credits',
            default => throw ValidationException::withMessages(['bucket' => 'Invalid wallet bucket.']),
        };
    }
}
