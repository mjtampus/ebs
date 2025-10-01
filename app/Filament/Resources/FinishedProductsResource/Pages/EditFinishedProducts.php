<?php

namespace App\Filament\Resources\FinishedProductsResource\Pages;

use App\Filament\Resources\FinishedProductsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinishedProducts extends EditRecord
{
    protected static string $resource = FinishedProductsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
