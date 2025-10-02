<?php

namespace App\Filament\Resources\CashManagementResource\Pages;

use App\Filament\Resources\CashManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashManagement extends ListRecords
{
    protected static string $resource = CashManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
