<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Services\TransactionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = auth()->id();

        return app(TransactionService::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
