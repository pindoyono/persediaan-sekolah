<?php

namespace App\Services;

use App\Events\LowStockDetected;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Cache;

class StockService
{
    /**
     * Get current stock for an item.
     * Uses cache with a short TTL for read performance.
     */
    public function getStock(Item $item): int
    {
        return Cache::remember("stock_item_{$item->id}", 60, function () use ($item) {
            $in  = StockMovement::where('item_id', $item->id)->where('type', 'IN')->sum('qty');
            $out = StockMovement::where('item_id', $item->id)->where('type', 'OUT')->sum('qty');

            return (int) ($in - $out);
        });
    }

    /**
     * Invalidate cache for an item's stock.
     */
    public function invalidateCache(int $itemId): void
    {
        Cache::forget("stock_item_{$itemId}");
    }

    /**
     * Record a stock movement and fire LowStockDetected if needed.
     */
    public function recordMovement(
        Item   $item,
        string $type,
        int    $qty,
        string $referenceType,
        int    $referenceId
    ): StockMovement {
        $movement = StockMovement::create([
            'item_id'        => $item->id,
            'type'           => $type,
            'qty'            => $qty,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
        ]);

        $this->invalidateCache($item->id);

        // Check low stock after recording OUT movement
        if ($type === 'OUT') {
            $currentStock = $this->getStock($item);
            if ($currentStock <= $item->min_stock) {
                event(new LowStockDetected($item, $currentStock));
            }
        }

        return $movement;
    }

    /**
     * Assert that an OUT transaction won't cause negative stock.
     *
     * @throws \RuntimeException
     */
    public function assertSufficientStock(Item $item, int $qty): void
    {
        $current = $this->getStock($item);
        if ($current < $qty) {
            throw new \RuntimeException(
                "Stok tidak cukup untuk item [{$item->name}]. Stok saat ini: {$current}, dibutuhkan: {$qty}."
            );
        }
    }
}
