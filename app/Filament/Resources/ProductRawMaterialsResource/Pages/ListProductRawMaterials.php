<?php

namespace App\Filament\Resources\ProductRawMaterialsResource\Pages;

use App\Filament\Resources\ProductRawMaterialsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductRawMaterials extends ListRecords
{
    protected static string $resource = ProductRawMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
