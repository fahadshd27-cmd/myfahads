<?php

namespace App\Http\Controllers;

use App\Models\UserInventoryItem;
use App\Services\InventoryActionService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryActionService $inventory,
        private readonly WalletService $wallets,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $this->wallets->ensureWallet($user)->fresh();

        $items = UserInventoryItem::query()
            ->where('user_id', $user->id)
            ->with(['item', 'spin.box'])
            ->latest()
            ->paginate(20);

        return view('inventory.index', [
            'wallet' => $wallet,
            'items' => $items,
        ]);
    }

    public function keep(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $item = UserInventoryItem::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->with(['item', 'spin'])
            ->firstOrFail();

        $updated = $this->inventory->keep($request->user(), $item);

        return $this->jsonResponse($request, $updated, 'Item added to inventory.');
    }

    public function save(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $item = UserInventoryItem::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->with(['item', 'spin'])
            ->firstOrFail();

        $updated = $this->inventory->save($request->user(), $item);

        return $this->jsonResponse($request, $updated, 'Item added to inventory.');
    }

    public function claim(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $item = UserInventoryItem::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->with(['item', 'spin'])
            ->firstOrFail();

        $updated = $this->inventory->claim($request->user(), $item);

        return $this->jsonResponse($request, $updated, 'Item marked as claimed.');
    }

    public function sell(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $item = UserInventoryItem::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->with(['item', 'spin'])
            ->firstOrFail();

        $idempotencyKey = $request->header('Idempotency-Key') ?: null;
        $updated = $this->inventory->sell($request->user(), $item, $idempotencyKey);

        return $this->jsonResponse($request, $updated, 'Item sold and credits added to sale balance.');
    }

    private function jsonResponse(Request $request, UserInventoryItem $updated, string $statusMessage): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'item' => $updated,
                'balance' => $this->wallets->spendableBalance($request->user()),
            ]);
        }

        return back()->with('status', $statusMessage);
    }
}
