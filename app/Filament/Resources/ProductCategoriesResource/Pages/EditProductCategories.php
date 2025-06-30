<?php

namespace App\Filament\Resources\ProductCategoriesResource\Pages;

use App\Filament\Resources\ProductCategoriesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductCategories extends EditRecord
{
    protected static string $resource = ProductCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
