<?php

namespace App\Imports;

use App\Models\Category;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;

    public int $updated = 0;

    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $kode = trim((string) ($row['kode'] ?? ''));
            $category = null;

            if ($kode !== '') {
                $category = Category::where('kode', $kode)->first();
            }

            if (! $category) {
                $category = Category::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
            }

            if ($category) {
                $this->inventoryService->updateCategory($category, ['name' => $name]);
                $this->updated++;

                continue;
            }

            if ($kode !== '' && Category::where('kode', $kode)->exists()) {
                throw new InvalidArgumentException('Kode kategori duplikat pada baris ' . ($index + 2) . '.');
            }

            $this->inventoryService->createCategoryFromImport([
                'kode' => $kode !== '' ? $kode : null,
                'name' => $name,
            ]);
            $this->created++;
        }
    }
}