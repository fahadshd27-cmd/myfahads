<?php

namespace App\Http\Controllers;

use App\Models\DepositOrder;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function __construct(private readonly DepositService $deposits) {}

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'gateway' => ['required', 'string'],
        ]);

        $order = $this->deposits->createDeposit($request->user(), (float) $data['amount'], (string) $data['gateway']);

        return response()->json([
            'reference' => $order->reference,
            'status' => $order->status,
            'checkout_url' => $order->checkout_url,
            'mode' => $order->mode,
            'gateway' => $order->gateway,
            'expires_at' => optional($order->expires_at)->toIso8601String(),
        ]);
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        $order = DepositOrder::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'reference' => $order->reference,
            'status' => $order->status,
            'amount' => (float) $order->amount_credits,
            'gateway' => $order->gateway,
            'mode' => $order->mode,
            'paid_at' => optional($order->paid_at)->toIso8601String(),
            'expires_at' => optional($order->expires_at)->toIso8601String(),
        ]);
    }

    public function testingCheckout(Request $request, string $reference): View|RedirectResponse
    {
        $order = DepositOrder::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->mode !== DepositService::MODE_TESTING) {
            return redirect()->route('wallet');
        }

        return view('deposits.testing-checkout', ['order' => $order]);
    }

    public function simulate(Request $request, string $reference): RedirectResponse
    {
        $order = DepositOrder::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate([
            'outcome' => ['required', 'in:paid,failed,pending'],
        ]);

        $this->deposits->simulateTestingOutcome($order, (string) $data['outcome']);

        return redirect()->back();
    }
}
