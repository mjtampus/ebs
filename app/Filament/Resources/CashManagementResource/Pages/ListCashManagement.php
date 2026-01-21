<?php

namespace App\Filament\Resources\CashManagementResource\Pages;

use App\Filament\Resources\CashManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashManagement extends ListRecords
{
    protected static string $resource = CashManagementResource::class;
    public function canCreate(): bool
    {
        return false;
    }
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
}
