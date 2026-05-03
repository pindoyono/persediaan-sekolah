<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Services\InventoryService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
        ];
    }
}
