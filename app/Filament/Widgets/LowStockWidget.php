<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\StockMovement;
use App\Services\StockService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected static ?string $heading = 'Barang Stok Kritis';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()->with('category')
            )
            ->columns([
                TextColumn::make('kode')->label('Kode'),
                TextColumn::make('name')->label('Nama Barang'),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('satuan')->label('Satuan'),
                TextColumn::make('min_stock')->label('Min Stok')->numeric(),
                TextColumn::make('current_stock')
                    ->label('Stok Saat Ini')
                    ->state(function (Item $record): int {
                        return app(StockService::class)->getStock($record);
                    })
                    ->badge()
                    ->color(fn(Item $record): string => 'danger'),
            ])
            ->defaultSort('name')
            ->paginated([5, 10])
            ->recordUrl(null);
    }

    public static function canView(): bool
    {
        // Only show widget if there are low stock items
        return Item::all()->contains(
            fn(Item $item) => app(StockService::class)->getStock($item) <= $item->min_stock
        );
    }
}
