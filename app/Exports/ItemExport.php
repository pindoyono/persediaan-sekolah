<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly ?string $sumberDana = null,
    ) {}

    public function query()
    {
        return Item::query()
            ->with('category')
            ->when($this->sumberDana, fn ($query) => $query->where('sumber_dana', $this->sumberDana))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Kode Barang',
            'Nama Barang',
            'Kode Kategori',
            'Nama Kategori',
            'Sumber Dana',
            'Satuan',
            'Minimum Stok',
        ];
    }

    public function map($item): array
    {
        $sumberDana = is_string($item->sumber_dana) ? $item->sumber_dana : $item->sumber_dana?->value;

        return [
            $item->kode,
            $item->name,
            $item->category?->kode,
            $item->category?->name,
            $sumberDana,
            $item->satuan,
            $item->min_stock,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}