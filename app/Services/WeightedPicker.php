<?php

namespace App\Services;

use App\Models\MysteryBoxItem;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class WeightedPicker
{
    /**
     * @param  Collection<int, MysteryBoxItem>  $items
     */
    public function pick(Collection $items, float $roll): MysteryBoxItem
    {
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'No active items in this box.']);
        }

        $totalWeight = (int) $items->sum(fn (MysteryBoxItem $item) => max(0, (int) $item->drop_weight));
        if ($totalWeight <= 0) {
            throw ValidationException::withMessages(['items' => 'Box has invalid weights.']);
        }

        // Convert roll [0,1) to integer ticket in [0, totalWeight-1]
        $ticket = (int) floor($roll * $totalWeight);
        $cursor = 0;

        foreach ($items as $item) {
            $w = max(0, (int) $item->drop_weight);
            if ($w === 0) {
                continue;
            }

            $cursor += $w;
            if ($ticket < $cursor) {
                return $item;
            }
        }

        // Fallback due to floating rounding edge.
        return $items->first();
    }
}
