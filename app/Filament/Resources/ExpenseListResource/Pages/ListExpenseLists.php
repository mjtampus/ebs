<?php

namespace App\Filament\Resources\ExpenseListResource\Pages;

use App\Filament\Resources\ExpenseListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseLists extends ListRecords
{
    protected static string $resource = ExpenseListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
