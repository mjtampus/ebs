<?php

namespace App\Filament\Resources\ShiftCashierSalesResource\Pages;

use App\Filament\Resources\ShiftCashierSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShiftManagement extends EditRecord
{
    protected static string $resource = ShiftCashierSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
