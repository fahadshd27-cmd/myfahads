<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserInventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryActionService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly UserProgressService $progress,
    ) {}

    public function keep(User $user, UserInventoryItem $inventoryItem): UserInventoryItem
    {
        return $this->save($user, $inventoryItem);
    }

    public function save(User $user, UserInventoryItem $inventoryItem): UserInventoryItem
    {
        return $this->transition($user, $inventoryItem, UserInventoryItem::STATE_SAVED);
    }

    public function claim(User $user, UserInventoryItem $inventoryItem): UserInventoryItem
    {
        return $this->transition($user, $inventoryItem, UserInventoryItem::STATE_CLAIMED);
    }

    public function sell(User $user, UserInventoryItem $inventoryItem, ?string $idempotencyKey = null): UserInventoryItem
    {
        if (! in_array($inventoryItem->state, [UserInventoryItem::STATE_PENDING, UserInventoryItem::STATE_SAVED], true)) {
            throw ValidationException::withMessages(['state' => 'Item can only be sold from pending or saved state.']);
        }

        return DB::transaction(function () use ($user, $inventoryItem, $idempotencyKey) {
            $inventoryItem = UserInventoryItem::query()
                ->whereKey($inventoryItem->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($inventoryItem->state, [UserInventoryItem::STATE_PENDING, UserInventoryItem::STATE_SAVED], true)) {
                throw ValidationException::withMessages(['state' => 'Item already finalized.']);
            }

            $item = $inventoryItem->item()->firstOrFail();
            $sellAmount = (float) data_get($inventoryItem->item_snapshot, 'sell_value_credits', $item->sell_value_credits);

            if ($sellAmount <= 0) {
                throw ValidationException::withMessages(['sell' => 'This item is not sellable.']);
            }

            $this->wallets->credit(
                user: $user,
                amount: $sellAmount,
                type: 'item_sell',
                meta: [
                    'inventory_item_id' => $inventoryItem->id,
                    'item_id' => $item->id,
                    'source_spin_id' => $inventoryItem->box_spin_id,
                ],
                idempotencyKey: $idempotencyKey,
                referenceType: UserInventoryItem::class,
                referenceId: $inventoryItem->id,
                bucket: WalletService::BUCKET_SALE,
                creditSource: WalletService::BUCKET_SALE,
                originContext: ['kind' => 'inventory_sell'],
            );

            $inventoryItem->state = UserInventoryItem::STATE_SOLD;
            $inventoryItem->sell_amount_credits = $sellAmount;
            $inventoryItem->acted_at = now();
            $inventoryItem->claim_status = 'sold';
            $inventoryItem->save();

            $this->progress->registerInventoryAction($inventoryItem, UserInventoryItem::STATE_SOLD);

            return $inventoryItem->fresh(['item', 'spin']);
        });
    }

    private function transition(User $user, UserInventoryItem $inventoryItem, string $toState): UserInventoryItem
    {
        if (! in_array($inventoryItem->state, [UserInventoryItem::STATE_PENDING, UserInventoryItem::STATE_SAVED], true)) {
            throw ValidationException::withMessages(['state' => 'Item already finalized.']);
        }

        if (! in_array($toState, [UserInventoryItem::STATE_SAVED, UserInventoryItem::STATE_CLAIMED], true)) {
            throw ValidationException::withMessages(['state' => 'Invalid transition.']);
        }

        return DB::transaction(function () use ($user, $inventoryItem, $toState) {
            $inventoryItem = UserInventoryItem::query()
                ->whereKey($inventoryItem->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($inventoryItem->state, [UserInventoryItem::STATE_PENDING, UserInventoryItem::STATE_SAVED], true)) {
                throw ValidationException::withMessages(['state' => 'Item already finalized.']);
            }

            $inventoryItem->state = $toState;
            $inventoryItem->claim_status = $toState === UserInventoryItem::STATE_CLAIMED ? 'claimed' : 'saved';
            $inventoryItem->acted_at = now();
            $inventoryItem->claimed_at = $toState === UserInventoryItem::STATE_CLAIMED ? now() : null;
            $inventoryItem->save();

            $this->progress->registerInventoryAction($inventoryItem, $toState);

            return $inventoryItem->fresh(['item', 'spin']);
        });
    }
}
