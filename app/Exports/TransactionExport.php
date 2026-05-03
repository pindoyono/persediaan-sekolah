<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, ShouldQueue
{
    public function __construct(
        private readonly ?string $fromDate = null,
        private readonly ?string $untilDate = null,
    ) {}

    public function query()
    {
        return Transaction::query()
            ->with(['details.item', 'creator'])
            ->when($this->fromDate, fn($q) => $q->whereDate('tanggal', '>=', $this->fromDate))
            ->when($this->untilDate, fn($q) => $q->whereDate('tanggal', '<=', $this->untilDate))
            ->orderBy('tanggal')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Kode Transaksi',
            'Tipe',
            'Tanggal',
            'Dibuat Oleh',
            'Kode Barang',
            'Nama Barang',
            'Qty',
            'Satuan',
            'Keterangan',
        ];
    }

    public function map($transaction): array
    {
        $rows = [];

        foreach ($transaction->details as $detail) {
            $rows[] = [
                $transaction->kode,
                $transaction->type,
                $transaction->tanggal->format('d/m/Y'),
                $transaction->creator?->name ?? '-',
                $detail->item->kode,
                $detail->item->name,
                $detail->qty,
                $detail->item->satuan,
                $transaction->keterangan ?? '-',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Transaksi';
    }
}
