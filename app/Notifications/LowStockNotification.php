<?php

namespace App\Notifications;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Item $item,
        public readonly int  $currentStock,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'item_id'       => $this->item->id,
            'item_name'     => $this->item->name,
            'item_kode'     => $this->item->kode,
            'current_stock' => $this->currentStock,
            'min_stock'     => $this->item->min_stock,
            'message'       => "Stok item [{$this->item->name}] hampir habis! Stok saat ini: {$this->currentStock} (minimum: {$this->item->min_stock})",
        ];
    }
}
