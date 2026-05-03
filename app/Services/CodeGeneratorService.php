<?php

namespace App\Services;

use App\Models\CodeCounter;
use Illuminate\Support\Facades\DB;

class CodeGeneratorService
{
    /**
     * Generate a concurrency-safe sequential code using code_counters table.
     * Uses DB transaction + lockForUpdate to prevent race conditions.
     */
    public function generate(string $key, string $prefix, int $pad = 4): string
    {
        return DB::transaction(function () use ($key, $prefix, $pad) {
            $counter = CodeCounter::lockForUpdate()->firstOrCreate(
                ['key' => $key],
                ['value' => 0]
            );

            $counter->increment('value');
            $counter->refresh();

            return $prefix . str_pad($counter->value, $pad, '0', STR_PAD_LEFT);
        });
    }

    public function generateCategoryCode(): string
    {
        return $this->generate('category', 'CAT-');
    }

    public function generateItemCode(string $categoryKode): string
    {
        $key = 'item_' . strtolower($categoryKode);

        return $this->generate($key, 'BRG-' . strtoupper($categoryKode) . '-');
    }

    public function generateTransactionCode(string $type, string $date): string
    {
        $dateFormatted = \Carbon\Carbon::parse($date)->format('Ymd');
        $key = 'trx_' . strtolower($type) . '_' . $dateFormatted;

        return $this->generate($key, 'TRX-' . strtoupper($type) . '-' . $dateFormatted . '-');
    }
}
