<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Services\CodeGeneratorService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService(new CodeGeneratorService());
    }

    // ─── Category ─────────────────────────────────────────────────────────────

    public function test_create_category_generates_kode_and_slug(): void
    {
        $category = $this->service->createCategory(['name' => 'Alat Tulis']);

        $this->assertEquals('CAT-0001', $category->kode);
        $this->assertEquals('Alat Tulis', $category->name);
        $this->assertEquals('alat-tulis', $category->slug);
    }

    public function test_create_category_increments_kode(): void
    {
        $this->service->createCategory(['name' => 'Pertama']);
        $second = $this->service->createCategory(['name' => 'Kedua']);

        $this->assertEquals('CAT-0002', $second->kode);
    }

    public function test_create_category_generates_unique_slug_on_duplicate_name(): void
    {
        $this->service->createCategory(['name' => 'Furniture']);
        $duplicate = $this->service->createCategory(['name' => 'Furniture']);

        $this->assertEquals('furniture', Category::first()->slug);
        $this->assertEquals('furniture-1', $duplicate->slug);
    }

    public function test_create_category_persists_to_database(): void
    {
        $this->service->createCategory(['name' => 'Elektronik']);

        $this->assertDatabaseHas('categories', ['name' => 'Elektronik']);
    }

    public function test_update_category_changes_name_and_slug(): void
    {
        $category = $this->service->createCategory(['name' => 'Lama']);

        $updated = $this->service->updateCategory($category, ['name' => 'Baru']);

        $this->assertEquals('Baru', $updated->name);
        $this->assertEquals('baru', $updated->slug);
    }

    public function test_update_category_does_not_change_kode(): void
    {
        $category = $this->service->createCategory(['name' => 'Test']);
        $originalKode = $category->kode;

        $updated = $this->service->updateCategory($category, ['name' => 'Updated']);

        $this->assertEquals($originalKode, $updated->kode);
    }

    public function test_update_category_slug_is_unique_across_other_categories(): void
    {
        $catA = $this->service->createCategory(['name' => 'Kebersihan']);
        $catB = $this->service->createCategory(['name' => 'Kebersihan Plus']);

        // Rename catB to 'Kebersihan' which already exists as catA
        $updated = $this->service->updateCategory($catB, ['name' => 'Kebersihan']);

        $this->assertEquals('kebersihan-1', $updated->slug);
    }

    public function test_update_category_slug_unchanged_when_name_is_same(): void
    {
        $category = $this->service->createCategory(['name' => 'Same']);

        $updated = $this->service->updateCategory($category, ['name' => 'Same']);

        $this->assertEquals('same', $updated->slug);
    }

    // ─── Item ──────────────────────────────────────────────────────────────────

    public function test_create_item_generates_kode_from_category(): void
    {
        $category = $this->service->createCategory(['name' => 'ATK']);

        $item = $this->service->createItem([
            'name'        => 'Pensil',
            'category_id' => $category->id,
            'satuan'      => 'pcs',
            'min_stock'   => 5,
        ]);

        $this->assertStringStartsWith('BRG-CAT-0001-', $item->kode);
        $this->assertEquals('BRG-CAT-0001-0001', $item->kode);
    }

    public function test_create_item_increments_kode_per_category(): void
    {
        $category = $this->service->createCategory(['name' => 'ATK']);

        $this->service->createItem(['name' => 'Item A', 'category_id' => $category->id, 'satuan' => 'pcs', 'min_stock' => 0]);
        $second = $this->service->createItem(['name' => 'Item B', 'category_id' => $category->id, 'satuan' => 'pcs', 'min_stock' => 0]);

        $this->assertEquals('BRG-CAT-0001-0002', $second->kode);
    }

    public function test_create_item_kode_per_different_categories_are_independent(): void
    {
        $catA = $this->service->createCategory(['name' => 'Cat A']);
        $catB = $this->service->createCategory(['name' => 'Cat B']);

        $this->service->createItem(['name' => 'Item A', 'category_id' => $catA->id, 'satuan' => 'pcs', 'min_stock' => 0]);
        $itemB = $this->service->createItem(['name' => 'Item B', 'category_id' => $catB->id, 'satuan' => 'pcs', 'min_stock' => 0]);

        $this->assertEquals('BRG-CAT-0002-0001', $itemB->kode);
    }

    public function test_create_item_persists_to_database(): void
    {
        $category = $this->service->createCategory(['name' => 'Test']);

        $this->service->createItem(['name' => 'Spidol', 'category_id' => $category->id, 'satuan' => 'pcs', 'min_stock' => 2]);

        $this->assertDatabaseHas('items', ['name' => 'Spidol', 'satuan' => 'pcs', 'min_stock' => 2]);
    }

    public function test_create_item_sets_min_stock_default_to_zero(): void
    {
        $category = $this->service->createCategory(['name' => 'Test']);

        $item = $this->service->createItem(['name' => 'No Min', 'category_id' => $category->id, 'satuan' => 'pcs']);

        $this->assertEquals(0, $item->min_stock);
    }

    public function test_update_item_changes_name_and_satuan(): void
    {
        $category = $this->service->createCategory(['name' => 'Test']);
        $item = $this->service->createItem(['name' => 'Old Name', 'category_id' => $category->id, 'satuan' => 'pcs']);

        $updated = $this->service->updateItem($item, ['name' => 'New Name', 'satuan' => 'box']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('box', $updated->satuan);
    }

    public function test_update_item_cannot_change_kode(): void
    {
        $category = $this->service->createCategory(['name' => 'Test']);
        $item = $this->service->createItem(['name' => 'Item', 'category_id' => $category->id, 'satuan' => 'pcs']);
        $originalKode = $item->kode;

        $updated = $this->service->updateItem($item, ['kode' => 'FAKE-0000', 'name' => 'New']);

        $this->assertEquals($originalKode, $updated->kode);
    }

    public function test_update_item_cannot_change_category(): void
    {
        $catA = $this->service->createCategory(['name' => 'Cat A']);
        $catB = $this->service->createCategory(['name' => 'Cat B']);
        $item = $this->service->createItem(['name' => 'Item', 'category_id' => $catA->id, 'satuan' => 'pcs']);

        $updated = $this->service->updateItem($item, ['category_id' => $catB->id]);

        $this->assertEquals($catA->id, $updated->category_id);
    }
}
