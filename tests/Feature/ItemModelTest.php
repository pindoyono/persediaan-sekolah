<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemModelTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['kode' => 'CAT-0001', 'name' => 'Test', 'slug' => 'test']);
        $this->item = Item::create([
            'kode'        => 'BRG-0001',
            'name'        => 'Test Item',
            'category_id' => $this->category->id,
            'satuan'      => 'pcs',
            'min_stock'   => 10,
        ]);
    }

    public function test_current_stock_returns_zero_with_no_movements(): void
    {
        $this->assertEquals(0, $this->item->currentStock());
    }

    public function test_current_stock_returns_in_qty(): void
    {
        StockMovement::create([
            'item_id'        => $this->item->id,
            'type'           => 'IN',
            'qty'            => 50,
            'reference_type' => Transaction::class,
            'reference_id'   => 1,
        ]);

        $this->assertEquals(50, $this->item->currentStock());
    }

    public function test_current_stock_calculates_in_minus_out(): void
    {
        StockMovement::insert([
            ['item_id' => $this->item->id, 'type' => 'IN',  'qty' => 30, 'reference_type' => Transaction::class, 'reference_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $this->item->id, 'type' => 'OUT', 'qty' => 8,  'reference_type' => Transaction::class, 'reference_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertEquals(22, $this->item->currentStock());
    }

    public function test_current_stock_aggregates_multiple_movements(): void
    {
        StockMovement::insert([
            ['item_id' => $this->item->id, 'type' => 'IN',  'qty' => 10, 'reference_type' => Transaction::class, 'reference_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $this->item->id, 'type' => 'IN',  'qty' => 20, 'reference_type' => Transaction::class, 'reference_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $this->item->id, 'type' => 'OUT', 'qty' => 5,  'reference_type' => Transaction::class, 'reference_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $this->item->id, 'type' => 'OUT', 'qty' => 3,  'reference_type' => Transaction::class, 'reference_id' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 10 + 20 - 5 - 3 = 22
        $this->assertEquals(22, $this->item->currentStock());
    }

    public function test_is_low_stock_returns_true_when_stock_equals_min(): void
    {
        // min_stock = 10, stock = 10 → low stock
        StockMovement::create([
            'item_id' => $this->item->id, 'type' => 'IN', 'qty' => 10,
            'reference_type' => Transaction::class, 'reference_id' => 1,
        ]);

        $this->assertTrue($this->item->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_stock_below_min(): void
    {
        // min_stock = 10, stock = 5 → low stock
        StockMovement::create([
            'item_id' => $this->item->id, 'type' => 'IN', 'qty' => 5,
            'reference_type' => Transaction::class, 'reference_id' => 1,
        ]);

        $this->assertTrue($this->item->isLowStock());
    }

    public function test_is_low_stock_returns_false_when_stock_above_min(): void
    {
        // min_stock = 10, stock = 15 → not low
        StockMovement::create([
            'item_id' => $this->item->id, 'type' => 'IN', 'qty' => 15,
            'reference_type' => Transaction::class, 'reference_id' => 1,
        ]);

        $this->assertFalse($this->item->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_no_stock_and_min_is_zero(): void
    {
        // min_stock = 0, stock = 0 → equal → low stock
        $item = Item::create([
            'kode' => 'BRG-0002', 'name' => 'Zero Min',
            'category_id' => $this->category->id, 'satuan' => 'pcs', 'min_stock' => 0,
        ]);

        $this->assertTrue($item->isLowStock());
    }

    public function test_category_relationship_returns_correct_category(): void
    {
        $this->assertEquals($this->category->id, $this->item->category->id);
        $this->assertEquals('Test', $this->item->category->name);
    }

    public function test_stock_movements_relationship(): void
    {
        StockMovement::create([
            'item_id' => $this->item->id, 'type' => 'IN', 'qty' => 10,
            'reference_type' => Transaction::class, 'reference_id' => 1,
        ]);

        $this->assertCount(1, $this->item->stockMovements);
    }

    public function test_item_only_counts_own_movements(): void
    {
        $other = Item::create([
            'kode' => 'BRG-OTHER', 'name' => 'Other',
            'category_id' => $this->category->id, 'satuan' => 'pcs', 'min_stock' => 0,
        ]);

        StockMovement::insert([
            ['item_id' => $this->item->id, 'type' => 'IN', 'qty' => 10, 'reference_type' => Transaction::class, 'reference_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $other->id,      'type' => 'IN', 'qty' => 99, 'reference_type' => Transaction::class, 'reference_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertEquals(10, $this->item->currentStock());
    }
}
