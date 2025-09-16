<?php

namespace App\Filament\Resources\ExpenseListResource\Pages;

use App\Filament\Resources\ExpenseListResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseList extends CreateRecord
{
    protected static string $resource = ExpenseListResource::class;
}
