<?php

namespace Tests\Feature;

use App\Events\TransactionCreated;
use App\Models\Category;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CodeGeneratorService;
use App\Services\StockService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $service;
    private User $user;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TransactionService(
            new CodeGeneratorService(),
            new StockService(),
        );

        $this->user = User::factory()->create();

        $category = Category::create(['kode' => 'CAT-0001', 'name' => 'Test', 'slug' => 'test']);
        $this->item = Item::create([
            'kode'        => 'BRG-0001',
            'name'        => 'Test Item',
            'category_id' => $category->id,
            'satuan'      => 'pcs',
            'min_stock'   => 5,
        ]);
    }

    // ─── IN transaction ───────────────────────────────────────────────────────

    public function test_create_in_transaction_saves_header(): void
    {
        $transaction = $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'keterangan' => 'Initial stock',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 20]],
        ]);

        $this->assertDatabaseHas('transactions', [
            'type'       => 'IN',
            'keterangan' => 'Initial stock',
            'created_by' => $this->user->id,
        ]);
        $this->assertStringStartsWith('TRX-IN-', $transaction->kode);
    }

    public function test_create_in_transaction_saves_details(): void
    {
        $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 15]],
        ]);

        $this->assertDatabaseHas('transaction_details', [
            'item_id' => $this->item->id,
            'qty'     => 15,
        ]);
    }

    public function test_create_in_transaction_records_stock_movement(): void
    {
        $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 25]],
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type'    => 'IN',
            'qty'     => 25,
        ]);
    }

    public function test_create_in_transaction_increases_stock(): void
    {
        $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 30]],
        ]);

        $this->assertEquals(30, $this->item->currentStock());
    }

    public function test_create_in_transaction_with_multiple_items(): void
    {
        $cat = Category::create(['kode' => 'CAT-0002', 'name' => 'Cat2', 'slug' => 'cat2']);
        $item2 = Item::create([
            'kode' => 'BRG-0002', 'name' => 'Item 2',
            'category_id' => $cat->id, 'satuan' => 'box', 'min_stock' => 0,
        ]);

        $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [
                ['item_id' => $this->item->id, 'qty' => 10],
                ['item_id' => $item2->id,      'qty' => 5],
            ],
        ]);

        $this->assertDatabaseCount('transaction_details', 2);
        $this->assertEquals(10, $this->item->currentStock());
        $this->assertEquals(5, $item2->currentStock());
    }

    public function test_create_in_transaction_fires_transaction_created_event(): void
    {
        Event::fake([TransactionCreated::class]);

        $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 10]],
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    // ─── OUT transaction ──────────────────────────────────────────────────────

    public function test_create_out_transaction_decreases_stock(): void
    {
        // Stock IN first
        $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 20]],
        ]);

        $this->service->create([
            'type'       => 'OUT',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 8]],
        ]);

        $this->assertEquals(12, $this->item->currentStock());
    }

    public function test_create_out_transaction_records_out_movement(): void
    {
        $this->service->create([
            'type' => 'IN', 'tanggal' => '2026-05-03',
            'created_by' => $this->user->id,
            'details' => [['item_id' => $this->item->id, 'qty' => 20]],
        ]);

        $this->service->create([
            'type' => 'OUT', 'tanggal' => '2026-05-03',
            'created_by' => $this->user->id,
            'details' => [['item_id' => $this->item->id, 'qty' => 5]],
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type'    => 'OUT',
            'qty'     => 5,
        ]);
    }

    public function test_create_out_transaction_fails_with_insufficient_stock(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stok tidak cukup/');

        $this->service->create([
            'type'       => 'OUT',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 5]],
        ]);
    }

    public function test_create_out_transaction_is_atomic_on_failure(): void
    {
        // Item A has stock, Item B has none — full transaction should roll back
        $cat = Category::create(['kode' => 'CAT-0002', 'name' => 'Cat2', 'slug' => 'cat2']);
        $itemB = Item::create([
            'kode' => 'BRG-0002', 'name' => 'Item B',
            'category_id' => $cat->id, 'satuan' => 'pcs', 'min_stock' => 0,
        ]);

        $this->service->create([
            'type' => 'IN', 'tanggal' => '2026-05-03',
            'created_by' => $this->user->id,
            'details' => [['item_id' => $this->item->id, 'qty' => 20]],
        ]);

        try {
            $this->service->create([
                'type' => 'OUT', 'tanggal' => '2026-05-03',
                'created_by' => $this->user->id,
                'details' => [
                    ['item_id' => $this->item->id, 'qty' => 5],
                    ['item_id' => $itemB->id,      'qty' => 1], // will fail
                ],
            ]);
        } catch (\RuntimeException $e) {
            // Expected
        }

        // No OUT transactions should have been created
        $this->assertDatabaseMissing('transactions', ['type' => 'OUT']);
        // Item A stock should be unchanged
        $this->assertEquals(20, $this->item->currentStock());
    }

    public function test_transaction_kode_is_unique_per_type_and_date(): void
    {
        $t1 = $this->service->create([
            'type' => 'IN', 'tanggal' => '2026-05-03',
            'created_by' => $this->user->id,
            'details' => [['item_id' => $this->item->id, 'qty' => 5]],
        ]);

        $t2 = $this->service->create([
            'type' => 'IN', 'tanggal' => '2026-05-03',
            'created_by' => $this->user->id,
            'details' => [['item_id' => $this->item->id, 'qty' => 5]],
        ]);

        $this->assertNotEquals($t1->kode, $t2->kode);
    }

    public function test_returned_transaction_eager_loads_relations(): void
    {
        $transaction = $this->service->create([
            'type'       => 'IN',
            'tanggal'    => '2026-05-03',
            'created_by' => $this->user->id,
            'details'    => [['item_id' => $this->item->id, 'qty' => 10]],
        ]);

        $this->assertTrue($transaction->relationLoaded('details'));
        $this->assertTrue($transaction->relationLoaded('creator'));
        $this->assertCount(1, $transaction->details);
    }
}
