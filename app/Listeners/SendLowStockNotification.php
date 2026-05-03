<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLowStockNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(LowStockDetected $event): void
    {
        // Notify all admin users (adjust filter as needed)
        User::all()->each(function (User $user) use ($event) {
            $user->notify(new LowStockNotification($event->item, $event->currentStock));
        });
    }
}
