<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly string $defaultSumberDana = 'BOSNAS',
    ) {}

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

    public function array(): array
    {
        return [
            ['BRG-CAT-0001-0001', 'Pulpen Biru', 'CAT-0001', 'Alat Tulis', $this->defaultSumberDana, 'pcs', 10],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}