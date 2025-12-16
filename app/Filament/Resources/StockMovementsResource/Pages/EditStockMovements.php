<?php

namespace App\Filament\Resources\StockMovementsResource\Pages;

use App\Filament\Resources\StockMovementsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockMovements extends EditRecord
{
    protected static string $resource = StockMovementsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
