<?php

namespace App\Filament\Resources\ProductCategoriesResource\Pages;

use App\Filament\Resources\ProductCategoriesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
