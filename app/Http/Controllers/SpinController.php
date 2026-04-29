<?php

namespace App\Http\Controllers;

use App\Models\BoxSpin;
use App\Models\MysteryBox;
use App\Services\SpinService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpinController extends Controller
{
    public function __construct(
        private readonly SpinService $spins,
        private readonly WalletService $wallets,
    ) {}

    public function store(Request $request, string $slug): JsonResponse
    {
        $box = MysteryBox::query()->with('rewardProfile')->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'client_seed' => ['nullable', 'string', 'max:200'],
        ]);

        $spin = $this->spins->spin($request->user(), $box, (string) ($data['client_seed'] ?? ''));
        $wallet = $this->wallets->ensureWallet($request->user())->fresh();

        $reel = $this->buildReel($box, $spin->resultItem);

        return response()->json([
            'spin_id' => $spin->id,
            'inventory_item_id' => $spin->inventoryItem?->id,
            'inventory_state' => $spin->inventoryItem?->state,
            'box' => ['slug' => $box->slug, 'price' => (float) $box->price_credits],
            'winner' => [
                'id' => $spin->resultItem->id,
                'name' => $spin->resultItem->name,
                'image' => $spin->resultItem->imageUrl(),
                'item_type' => $spin->resultItem->item_type,
                'rarity' => $spin->resultItem->rarity,
                'value_tier' => $spin->resultItem->value_tier,
                'sell_value' => (float) $spin->resultItem->sell_value_credits,
                // Kept for backward-compat; equals sell_value in the simplified economy.
                'estimated_value' => (float) $spin->resultItem->sell_value_credits,
            ],
            'reel' => $reel['slots'],
            'stop_index' => $reel['stop_index'],
            'funding_source' => [
                'primary_bucket' => data_get($spin->meta, 'funding.primary_bucket'),
                'breakdown' => data_get($spin->meta, 'funding.breakdown', []),
            ],
            'balance' => $wallet->balanceSummary(),
            'fairness' => [
                'server_seed_hash' => $spin->server_seed_hash,
                'nonce' => (int) $spin->nonce,
                'roll_value' => (float) $spin->roll_value,
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $spins = BoxSpin::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->with(['box', 'resultItem', 'inventoryItem'])
            ->limit(50)
            ->get();

        return response()->json($spins);
    }

    public function fairness(Request $request, int $spinId): JsonResponse
    {
        $spin = BoxSpin::query()
            ->whereKey($spinId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'spin_id' => $spin->id,
            'server_seed_hash' => $spin->server_seed_hash,
            'server_seed_plain' => $spin->server_seed_plain,
            'client_seed' => $spin->client_seed,
            'nonce' => $spin->nonce,
            'roll_value' => (float) $spin->roll_value,
            'funding' => data_get($spin->meta, 'funding', []),
            'reason_trail' => data_get($spin->meta, 'reason_trail', []),
        ]);
    }

    private function buildReel(MysteryBox $box, $winner): array
    {
        $totalSlots = 180;
        $stopIndex = 150;
        $items = $box->activeItems()->inRandomOrder()->get()->values();

        $slots = collect(range(0, $totalSlots - 1))
            ->map(function (int $index) use ($items): array {
                $item = $items[$index % $items->count()];

                return [
                    'type' => 'item',
                    'id' => $item->id,
                    'name' => $item->name,
                    'image' => $item->imageUrl(),
                    'rarity' => $item->rarity,
                    'value' => (float) $item->sell_value_credits,
                ];
            })
            ->all();

        $slots[$stopIndex] = [
            'type' => 'item',
            'id' => $winner->id,
            'name' => $winner->name,
            'image' => $winner->imageUrl(),
            'rarity' => $winner->rarity,
            'value' => (float) $winner->sell_value_credits,
        ];

        return ['slots' => $slots, 'stop_index' => $stopIndex];
    }
}
