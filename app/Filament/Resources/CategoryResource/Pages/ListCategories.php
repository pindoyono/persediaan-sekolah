<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Exports\CategoryExport;
use App\Exports\CategoryTemplateExport;
use App\Filament\Resources\CategoryResource;
use App\Imports\CategoryImport;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): \App\Models\Category {
                    return app(InventoryService::class)->createCategory($data);
                }),
            Action::make('export')
                ->label('Export Excel')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action(function () {
                    return Excel::download(new CategoryExport(), 'categories-' . now()->format('Ymd-His') . '.xlsx');
                }),
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon(Heroicon::DocumentArrowDown)
                ->color('info')
                ->action(function () {
                    return Excel::download(new CategoryTemplateExport(), 'template-kategori.xlsx');
                }),
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
        ];
    }
}
