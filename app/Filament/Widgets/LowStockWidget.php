<?php

namespace App\Filament\Widgets;

use App\Models\Item;
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
        $sumberDana = session('sumber_dana', 'BOSNAS');

        return $table
            ->query(
                Item::query()
                    ->with('category')
                    ->where('sumber_dana', $sumberDana)
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
        // Only show widget if there are low stock items for the current sumber_dana
        return Item::where('sumber_dana', session('sumber_dana', 'BOSNAS'))
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = \'IN\' THEN qty ELSE -qty END), 0) FROM stock_movements WHERE item_id = items.id) <= min_stock')
            ->exists();
    }
}
