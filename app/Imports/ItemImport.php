<?php

namespace App\Imports;

use App\Enums\SumberDana;
use App\Models\Category;
use App\Models\Item;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;

    public int $updated = 0;

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly string $defaultSumberDana = 'BOSNAS',
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['nama_barang'] ?? $row['name'] ?? $row['nama'] ?? ''));

            if ($name === '') {
                continue;
            }

            $kode = trim((string) ($row['kode_barang'] ?? $row['kode'] ?? ''));
            $category = $this->resolveCategory($row, $index);
            $sumberDana = $this->resolveSumberDana($row, $index);
            $satuan = trim((string) ($row['satuan'] ?? ''));
            $minStock = (int) ($row['minimum_stok'] ?? $row['min_stock'] ?? 0);

            if ($satuan === '') {
                throw new InvalidArgumentException('Satuan wajib diisi pada baris ' . ($index + 2) . '.');
            }

            $item = $kode !== '' ? Item::where('kode', $kode)->first() : null;

            if ($item) {
                $this->inventoryService->updateItem($item, [
                    'name' => $name,
                    'sumber_dana' => $sumberDana,
                    'satuan' => $satuan,
                    'min_stock' => $minStock,
                ]);
                $this->updated++;

                continue;
            }

            $this->inventoryService->createItemFromImport([
                'kode' => $kode !== '' ? $kode : null,
                'name' => $name,
                'category_id' => $category->id,
                'sumber_dana' => $sumberDana,
                'satuan' => $satuan,
                'min_stock' => $minStock,
            ]);
            $this->created++;
        }
    }

    private function resolveCategory(array $row, int $index): Category
    {
        $categoryCode = trim((string) ($row['kode_kategori'] ?? $row['category_code'] ?? ''));
        $categoryName = trim((string) ($row['nama_kategori'] ?? $row['category_name'] ?? $row['kategori'] ?? ''));

        $category = null;

        if ($categoryCode !== '') {
            $category = Category::where('kode', $categoryCode)->first();
        }

        if (! $category && $categoryName !== '') {
            $category = Category::whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();
        }

        if (! $category) {
            throw new InvalidArgumentException('Kategori tidak ditemukan pada baris ' . ($index + 2) . '.');
        }

        return $category;
    }

    private function resolveSumberDana(array $row, int $index): string
    {
        $sumberDana = trim((string) ($row['sumber_dana'] ?? $this->defaultSumberDana));

        if (! in_array($sumberDana, array_map(fn (SumberDana $case) => $case->value, SumberDana::cases()), true)) {
            throw new InvalidArgumentException('Sumber dana tidak valid pada baris ' . ($index + 2) . '.');
        }

        return $sumberDana;
    }
}