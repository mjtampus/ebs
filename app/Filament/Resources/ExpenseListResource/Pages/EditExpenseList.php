<?php

namespace App\Filament\Resources\ExpenseListResource\Pages;

use App\Filament\Resources\ExpenseListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseList extends EditRecord
{
    protected static string $resource = ExpenseListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
