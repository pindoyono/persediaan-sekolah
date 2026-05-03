<?php

namespace App\Services;

use App\Events\TransactionCreated;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private readonly CodeGeneratorService $codeGenerator,
        private readonly StockService         $stockService,
    ) {}

    /**
     * Create a full transaction with details and stock movements.
     *
     * @param  array{type: string, tanggal: string, keterangan: ?string, created_by: int, details: array<array{item_id: int, qty: int}>}  $data
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate stock for OUT transactions
            if ($data['type'] === 'OUT') {
                foreach ($data['details'] as $detail) {
                    $item = Item::lockForUpdate()->findOrFail($detail['item_id']);
                    $this->stockService->assertSufficientStock($item, $detail['qty']);
                }
            }

            // 2. Generate transaction code
            $kode = $this->codeGenerator->generateTransactionCode($data['type'], $data['tanggal']);

            // 3. Save transaction header
            $transaction = Transaction::create([
                'kode'        => $kode,
                'type'        => $data['type'],
                'tanggal'     => $data['tanggal'],
                'created_by'  => $data['created_by'],
                'keterangan'  => $data['keterangan'] ?? null,
            ]);

            // 4. Save details and stock movements
            foreach ($data['details'] as $detail) {
                $item = Item::find($detail['item_id']);

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'item_id'        => $item->id,
                    'qty'            => $detail['qty'],
                ]);

                // 5. Record stock movement
                $this->stockService->recordMovement(
                    $item,
                    $data['type'],
                    $detail['qty'],
                    Transaction::class,
                    $transaction->id
                );
            }

            // 6. Trigger event
            event(new TransactionCreated($transaction));

            return $transaction->load('details.item', 'creator');
        });
    }
}
