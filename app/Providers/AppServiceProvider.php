<?php

namespace App\Providers;

use App\Events\LowStockDetected;
use App\Events\TransactionCreated;
use App\Listeners\SendLowStockNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\CodeGeneratorService::class);
        $this->app->singleton(\App\Services\StockService::class);
        $this->app->singleton(\App\Services\InventoryService::class);
        $this->app->singleton(\App\Services\TransactionService::class);
    }

    public function boot(): void
    {
        Event::listen(LowStockDetected::class, SendLowStockNotification::class);
    }
}
