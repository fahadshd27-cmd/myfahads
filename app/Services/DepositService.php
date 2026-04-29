<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DepositOrder;
use App\Models\DepositWebhookEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DepositService
{
    public const GATEWAY_PAYLINK = 'paylink';

    public const GATEWAY_BITPAY = 'bitpay';

    public const MODE_TESTING = 'testing';

    public const MODE_PRODUCTION = 'production';

    public function __construct(private readonly WalletService $wallets) {}

    public function currentMode(): string
    {
        return AppSetting::getString('payments.mode', self::MODE_TESTING) ?? self::MODE_TESTING;
    }

    public function createDeposit(User $user, float $amount, string $gateway): DepositOrder
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Deposit amount must be positive.']);
        }
        if (! in_array($gateway, [self::GATEWAY_PAYLINK, self::GATEWAY_BITPAY], true)) {
            throw ValidationException::withMessages(['gateway' => 'Invalid gateway.']);
        }

        $mode = $this->currentMode();
        $expiresMinutes = AppSetting::getInt('payments.pending_expiry_minutes', 30);
        $reference = strtoupper(Str::random(12));

        return DB::transaction(function () use ($user, $amount, $gateway, $mode, $expiresMinutes, $reference) {
            $order = DepositOrder::query()->create([
                'user_id' => $user->id,
                'reference' => $reference,
                'gateway' => $gateway,
                'mode' => $mode,
                'amount_credits' => $amount,
                'status' => DepositOrder::STATUS_CREATED,
                'expires_at' => now()->addMinutes($expiresMinutes),
            ]);

            $order->checkout_url = $this->resolveCheckoutUrl($order);
            $order->status = DepositOrder::STATUS_REDIRECTED;
            $order->save();

            return $order;
        });
    }

    private function resolveCheckoutUrl(DepositOrder $order): string
    {
        if ($order->mode === self::MODE_TESTING) {
            return url('/deposits/'.$order->reference.'/checkout');
        }

        $key = $order->gateway === self::GATEWAY_BITPAY
            ? 'payments.bitpay.checkout_url_template'
            : 'payments.paylink.checkout_url_template';

        $template = AppSetting::getString($key, null);
        if (! $template) {
            throw ValidationException::withMessages(['gateway' => 'Gateway checkout URL is not configured.']);
        }

        return strtr($template, [
            '{reference}' => $order->reference,
            '{amount}' => (string) $order->amount_credits,
            '{user_email}' => (string) $order->user()->value('email'),
        ]);
    }

    public function markExpiredDeposits(): int
    {
        return DepositOrder::query()
            ->whereIn('status', [DepositOrder::STATUS_CREATED, DepositOrder::STATUS_REDIRECTED, DepositOrder::STATUS_PENDING])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => DepositOrder::STATUS_EXPIRED]);
    }

    /**
     * Generic webhook processor:
     * expects payload fields: reference, status (paid|failed|pending), external_id/event_id optional.
     */
    public function handleWebhook(Request $request, string $gateway, string $secretSettingKey): DepositWebhookEvent
    {
        $payload = $request->json()->all();
        if (! is_array($payload)) {
            $payload = [];
        }

        $signatureHeader = $request->header('X-Signature') ?? $request->header('X-Hub-Signature') ?? $request->header('Stripe-Signature');
        $secret = AppSetting::getString($secretSettingKey, '');
        $isValid = $this->verifySignature($request->getContent(), (string) $signatureHeader, (string) $secret);

        $eventId = data_get($payload, 'event_id') ?? data_get($payload, 'id');
        $externalId = data_get($payload, 'external_id') ?? data_get($payload, 'payment_id') ?? data_get($payload, 'invoice_id');
        $reference = data_get($payload, 'reference') ?? data_get($payload, 'metadata.reference');

        return DB::transaction(function () use ($gateway, $eventId, $externalId, $reference, $signatureHeader, $isValid, $payload) {
            $event = DepositWebhookEvent::query()->create([
                'gateway' => $gateway,
                'event_id' => $eventId ? (string) $eventId : null,
                'external_id' => $externalId ? (string) $externalId : null,
                'signature_header' => $signatureHeader,
                'is_signature_valid' => $isValid,
                'is_processed' => false,
                'payload' => $payload,
            ]);

            if (! $isValid) {
                return $event;
            }

            $order = null;
            if ($reference) {
                $order = DepositOrder::query()->where('reference', (string) $reference)->lockForUpdate()->first();
            }

            if ($order && $externalId && ! $order->external_id) {
                $order->external_id = (string) $externalId;
            }

            if ($order) {
                $event->deposit_order_id = $order->id;
                $event->save();

                $status = strtolower((string) (data_get($payload, 'status') ?? 'pending'));
                if (in_array($status, ['paid', 'succeeded', 'success'], true)) {
                    $this->finalizePaid($order);
                    $event->is_processed = true;
                    $event->save();
                } elseif (in_array($status, ['failed', 'canceled', 'cancelled'], true)) {
                    $order->status = DepositOrder::STATUS_FAILED;
                    $order->save();
                    $event->is_processed = true;
                    $event->save();
                } else {
                    if (! $order->isFinal()) {
                        $order->status = DepositOrder::STATUS_PENDING;
                        $order->save();
                    }
                }
            }

            return $event;
        });
    }

    public function finalizePaid(DepositOrder $order): void
    {
        if ($order->status === DepositOrder::STATUS_PAID) {
            return;
        }

        if ($order->isFinal() && $order->status !== DepositOrder::STATUS_PAID) {
            throw ValidationException::withMessages(['deposit' => 'Deposit already finalized.']);
        }

        $order->status = DepositOrder::STATUS_PAID;
        $order->paid_at = now();
        $order->save();

        $this->wallets->credit(
            user: $order->user()->firstOrFail(),
            amount: (float) $order->amount_credits,
            type: 'deposit_credit',
            meta: ['gateway' => $order->gateway, 'reference' => $order->reference],
            idempotencyKey: 'deposit:'.$order->reference,
            referenceType: DepositOrder::class,
            referenceId: $order->id,
            bucket: WalletService::BUCKET_REAL_MONEY,
            creditSource: WalletService::BUCKET_REAL_MONEY,
            originContext: ['kind' => 'deposit', 'gateway' => $order->gateway],
        );
    }

    public function simulateTestingOutcome(DepositOrder $order, string $outcome): DepositOrder
    {
        if ($order->mode !== self::MODE_TESTING) {
            throw ValidationException::withMessages(['mode' => 'Simulation only allowed in testing mode.']);
        }

        if ($order->isFinal()) {
            return $order;
        }

        return DB::transaction(function () use ($order, $outcome) {
            $order = DepositOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $outcome = strtolower($outcome);
            if ($outcome === 'paid') {
                $this->finalizePaid($order);
            } elseif ($outcome === 'failed') {
                $order->status = DepositOrder::STATUS_FAILED;
                $order->save();
            } else {
                $order->status = DepositOrder::STATUS_PENDING;
                $order->save();
            }

            return $order->fresh();
        });
    }

    private function verifySignature(string $rawBody, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        // v1 generic: signature header contains hex hmac sha256 of raw body.
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, trim($signatureHeader));
    }
}
