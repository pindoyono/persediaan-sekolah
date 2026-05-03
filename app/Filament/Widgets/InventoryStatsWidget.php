<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\Transaction;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class InventoryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $stats = Cache::remember('inventory_stats', 120, function () {
            return [
                'total_items'      => Item::count(),
                'total_categories' => \App\Models\Category::count(),
                'trx_today_in'     => Transaction::whereDate('tanggal', today())->where('type', 'IN')->count(),
                'trx_today_out'    => Transaction::whereDate('tanggal', today())->where('type', 'OUT')->count(),
                'low_stock_count'  => Item::all()->filter(
                    fn(Item $item) => app(\App\Services\StockService::class)->getStock($item) <= $item->min_stock
                )->count(),
            ];
        });

        return [
            Stat::make('Total Barang', $stats['total_items'])
                ->icon(Heroicon::ArchiveBox)
                ->color('primary'),

            Stat::make('Total Kategori', $stats['total_categories'])
                ->icon(Heroicon::Tag)
                ->color('info'),

            Stat::make('Transaksi Masuk Hari Ini', $stats['trx_today_in'])
                ->icon(Heroicon::ArrowDownTray)
                ->color('success'),

            Stat::make('Transaksi Keluar Hari Ini', $stats['trx_today_out'])
                ->icon(Heroicon::ArrowUpTray)
                ->color('warning'),

            Stat::make('Barang Stok Kritis', $stats['low_stock_count'])
                ->icon(Heroicon::ExclamationTriangle)
                ->color($stats['low_stock_count'] > 0 ? 'danger' : 'success'),
        ];
    }
}
