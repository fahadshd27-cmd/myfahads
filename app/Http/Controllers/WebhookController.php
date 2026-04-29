<?php

namespace App\Http\Controllers;

use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private readonly DepositService $deposits) {}

    public function paylink(Request $request): JsonResponse
    {
        $event = $this->deposits->handleWebhook($request, DepositService::GATEWAY_PAYLINK, 'payments.paylink.webhook_secret');

        return response()->json([
            'ok' => true,
            'signature_valid' => $event->is_signature_valid,
            'processed' => $event->is_processed,
        ]);
    }

    public function bitpay(Request $request): JsonResponse
    {
        $event = $this->deposits->handleWebhook($request, DepositService::GATEWAY_BITPAY, 'payments.bitpay.webhook_secret');

        return response()->json([
            'ok' => true,
            'signature_valid' => $event->is_signature_valid,
            'processed' => $event->is_processed,
        ]);
    }
}
