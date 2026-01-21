<?php

namespace App\Filament\Resources\ShiftCashierSalesResource\Pages;

use App\Filament\Resources\ShiftCashierSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShiftManagement extends ListRecords
{
    protected static string $resource = ShiftCashierSalesResource::class;

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
