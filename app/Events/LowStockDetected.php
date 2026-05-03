<?php

namespace App\Events;

use App\Models\Item;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Item $item,
        public readonly int  $currentStock,
    ) {}
}
