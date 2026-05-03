<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Models\Item;
use App\Services\InventoryService;
use App\Services\StockService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;
    protected static ?string $navigationLabel = 'Master Barang';
    protected static ?string $modelLabel = 'Barang';
    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::ArchiveBox;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('kode')
                ->label('Kode Barang')
                ->disabled()
                ->placeholder('Auto-generated')
                ->visibleOn('edit'),

            TextInput::make('name')
                ->label('Nama Barang')
                ->required()
                ->maxLength(150),

            Select::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->disabledOn('edit'),

            TextInput::make('satuan')
                ->label('Satuan')
                ->required()
                ->maxLength(30)
                ->placeholder('pcs, kg, liter, ...'),

            TextInput::make('min_stock')
                ->label('Minimum Stok')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->searchable()->sortable(),
                TextColumn::make('satuan')->label('Satuan'),
                TextColumn::make('min_stock')->label('Min Stok')->numeric()->sortable(),
                TextColumn::make('current_stock')
                    ->label('Stok Saat Ini')
                    ->state(function (Item $record): int {
                        return app(StockService::class)->getStock($record);
                    })
                    ->badge()
                    ->color(function (Item $record): string {
                        $stock = app(StockService::class)->getStock($record);
                        return $stock <= $record->min_stock ? 'danger' : 'success';
                    }),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (Item $record, array $data): Item {
                        return app(InventoryService::class)->updateItem($record, $data);
                    }),
                DeleteAction::make()
                    ->before(function (Item $record, DeleteAction $action) {
                        if ($record->transactionDetails()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat dihapus')
                                ->body('Barang ini sudah digunakan dalam transaksi.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
        ];
    }
}
