<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Exports\CategoryExport;
use App\Exports\CategoryTemplateExport;
use App\Imports\CategoryImport;
use App\Models\Category;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

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
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->action(fn () => Excel::download(new CategoryExport(), 'categories-' . now()->format('Ymd-His') . '.xlsx')),
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon(Heroicon::DocumentArrowDown)
                    ->color('info')
                    ->action(fn () => Excel::download(new CategoryTemplateExport(), 'template-kategori.xlsx')),
                Action::make('import')
                    ->label('Import Excel')
                    ->icon(Heroicon::ArrowUpTray)
                    ->color('primary')
                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->required()
                            ->helperText('Gunakan file hasil export kategori atau file dengan header Kode dan Nama.'),
                    ])
                    ->action(function (array $data) {
                        $path = Storage::disk('local')->path($data['file']);
                        $import = new CategoryImport(app(InventoryService::class));

                        try {
                            Excel::import($import, $path);

                            Notification::make()
                                ->success()
                                ->title('Import kategori selesai')
                                ->body("{$import->created} kategori dibuat, {$import->updated} kategori diperbarui.")
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Import kategori gagal')
                                ->body($exception->getMessage())
                                ->send();
                        } finally {
                            Storage::disk('local')->delete($data['file']);
                        }
                    }),
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
