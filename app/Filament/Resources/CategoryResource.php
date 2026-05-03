<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Services\InventoryService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationLabel = 'Kategori';
    protected static ?string $modelLabel = 'Kategori';
    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::Tag;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('kode')
                ->label('Kode')
                ->disabled()
                ->placeholder('Auto-generated')
                ->visibleOn('edit'),

            TextInput::make('name')
                ->label('Nama Kategori')
                ->required()
                ->maxLength(100),

            TextInput::make('slug')
                ->label('Slug')
                ->disabled()
                ->placeholder('Auto-generated')
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (Category $record, array $data): Category {
                        return app(InventoryService::class)->updateCategory($record, $data);
                    }),
                DeleteAction::make()
                    ->before(function (Category $record, DeleteAction $action) {
                        if ($record->items()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat dihapus')
                                ->body('Kategori ini masih memiliki item terkait.')
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
            'index' => Pages\ListCategories::route('/'),
        ];
    }
}
