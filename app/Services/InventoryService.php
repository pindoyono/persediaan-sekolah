<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Str;

class InventoryService
{
    public function __construct(
        private readonly CodeGeneratorService $codeGenerator,
    ) {}

    public function createCategory(array $data): Category
    {
        $kode = $this->codeGenerator->generateCategoryCode();
        $slug = Str::slug($data['name']);

        // Ensure slug uniqueness
        $originalSlug = $slug;
        $count = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return Category::create([
            'kode' => $kode,
            'name' => $data['name'],
            'slug' => $slug,
        ]);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $slug = Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $data['slug'] = $slug;
        }

        $category->update($data);

        return $category->refresh();
    }

    public function createItem(array $data): Item
    {
        $category = Category::findOrFail($data['category_id']);
        $kode = $this->codeGenerator->generateItemCode($category->kode);

        return Item::create([
            'kode'        => $kode,
            'name'        => $data['name'],
            'category_id' => $data['category_id'],
            'satuan'      => $data['satuan'],
            'min_stock'   => $data['min_stock'] ?? 0,
        ]);
    }

    public function updateItem(Item $item, array $data): Item
    {
        // Prevent changing kode and category_id
        unset($data['kode'], $data['category_id']);
        $item->update($data);

        return $item->refresh();
    }
}
