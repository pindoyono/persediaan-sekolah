<?php

namespace Tests\Feature;

use App\Events\LowStockDetected;
use App\Models\Category;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockService();

        $category = Category::create(['kode' => 'CAT-0001', 'name' => 'Test', 'slug' => 'test']);
        $this->item = Item::create([
            'kode'        => 'BRG-0001',
            'name'        => 'Test Item',
            'category_id' => $category->id,
            'satuan'      => 'pcs',
            'min_stock'   => 5,
        ]);
    }

    public function test_get_stock_returns_zero_for_new_item(): void
    {
        $this->assertEquals(0, $this->service->getStock($this->item));
    }

    public function test_get_stock_calculates_in_movements(): void
    {
        StockMovement::create([
            'item_id'        => $this->item->id,
            'type'           => 'IN',
            'qty'            => 20,
            'reference_type' => Transaction::class,
            'reference_id'   => 1,
        ]);

        Cache::forget("stock_item_{$this->item->id}");

        $this->assertEquals(20, $this->service->getStock($this->item));
    }

    public function test_get_stock_calculates_in_minus_out(): void
    {
        StockMovement::insert([
            ['item_id' => $this->item->id, 'type' => 'IN', 'qty' => 30, 'reference_type' => Transaction::class, 'reference_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $this->item->id, 'type' => 'OUT', 'qty' => 10, 'reference_type' => Transaction::class, 'reference_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Cache::forget("stock_item_{$this->item->id}");

        $this->assertEquals(20, $this->service->getStock($this->item));
    }

    public function test_get_stock_uses_cache(): void
    {
        // Prime cache with a value
        Cache::put("stock_item_{$this->item->id}", 999, 60);

        $this->assertEquals(999, $this->service->getStock($this->item));
    }

    public function test_invalidate_cache_clears_value(): void
    {
        Cache::put("stock_item_{$this->item->id}", 999, 60);

        $this->service->invalidateCache($this->item->id);

        $this->assertNull(Cache::get("stock_item_{$this->item->id}"));
    }

    public function test_record_movement_creates_stock_movement_record(): void
    {
        $movement = $this->service->recordMovement(
            $this->item, 'IN', 15, Transaction::class, 1
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type'    => 'IN',
            'qty'     => 15,
        ]);
    }

    public function test_record_in_movement_does_not_fire_low_stock_event(): void
    {
        Event::fake([LowStockDetected::class]);

        $this->service->recordMovement($this->item, 'IN', 50, Transaction::class, 1);

        Event::assertNotDispatched(LowStockDetected::class);
    }

    public function test_record_out_movement_fires_low_stock_event_when_stock_below_minimum(): void
    {
        Event::fake([LowStockDetected::class]);

        // First add stock
        $this->service->recordMovement($this->item, 'IN', 10, Transaction::class, 1);
        // Take out so stock goes to 5 (equals min_stock of 5)
        $this->service->recordMovement($this->item, 'OUT', 5, Transaction::class, 2);

        Event::assertDispatched(LowStockDetected::class, function ($event) {
            return $event->item->id === $this->item->id;
        });
    }

    public function test_record_out_movement_does_not_fire_low_stock_when_stock_is_sufficient(): void
    {
        Event::fake([LowStockDetected::class]);

        $this->service->recordMovement($this->item, 'IN', 100, Transaction::class, 1);
        $this->service->recordMovement($this->item, 'OUT', 5, Transaction::class, 2);

        Event::assertNotDispatched(LowStockDetected::class);
    }

    public function test_record_movement_invalidates_cache(): void
    {
        Cache::put("stock_item_{$this->item->id}", 999, 60);

        $this->service->recordMovement($this->item, 'IN', 10, Transaction::class, 1);

        // Cache should be cleared, so it re-calculates from DB
        $stock = $this->service->getStock($this->item);
        $this->assertEquals(10, $stock);
    }

    public function test_assert_sufficient_stock_passes_when_stock_is_enough(): void
    {
        $this->service->recordMovement($this->item, 'IN', 20, Transaction::class, 1);

        // Should not throw
        $this->service->assertSufficientStock($this->item, 10);

        $this->assertTrue(true); // reached here = no exception
    }

    public function test_assert_sufficient_stock_passes_for_exact_amount(): void
    {
        $this->service->recordMovement($this->item, 'IN', 10, Transaction::class, 1);

        $this->service->assertSufficientStock($this->item, 10);

        $this->assertTrue(true);
    }

    public function test_assert_sufficient_stock_throws_when_insufficient(): void
    {
        $this->service->recordMovement($this->item, 'IN', 5, Transaction::class, 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stok tidak cukup/');

        $this->service->assertSufficientStock($this->item, 10);
    }

    public function test_assert_sufficient_stock_throws_when_zero_stock(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->assertSufficientStock($this->item, 1);
    }
}
