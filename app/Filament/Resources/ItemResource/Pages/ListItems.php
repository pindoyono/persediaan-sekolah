<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Exports\ItemExport;
use App\Exports\ItemTemplateExport;
use App\Filament\Resources\ItemResource;
use App\Imports\ItemImport;
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

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): \App\Models\Item {
                    return app(InventoryService::class)->createItem($data);
                }),
            Action::make('export')
                ->label('Export Excel')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action(function () {
                    $sumberDana = session('sumber_dana', 'BOSNAS');

                    return Excel::download(
                        new ItemExport($sumberDana),
                        'items-' . strtolower($sumberDana) . '-' . now()->format('Ymd-His') . '.xlsx'
                    );
                }),
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon(Heroicon::DocumentArrowDown)
                ->color('info')
                ->action(function () {
                    return Excel::download(
                        new ItemTemplateExport(session('sumber_dana', 'BOSNAS')),
                        'template-barang.xlsx'
                    );
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
                        ->helperText('Gunakan file hasil export barang. Jika kolom sumber dana kosong, sistem memakai sumber dana aktif.'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $import = new ItemImport(app(InventoryService::class), session('sumber_dana', 'BOSNAS'));

                    try {
                        Excel::import($import, $path);

                        Notification::make()
                            ->success()
                            ->title('Import barang selesai')
                            ->body("{$import->created} barang dibuat, {$import->updated} barang diperbarui.")
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Import barang gagal')
                            ->body($exception->getMessage())
                            ->send();
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }
                }),
        ];
    }
}
