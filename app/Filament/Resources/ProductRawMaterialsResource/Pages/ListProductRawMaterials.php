<?php

namespace App\Filament\Resources\ProductRawMaterialsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductRawMaterialsResource;

class ListProductRawMaterials extends ListRecords
{
    protected static string $resource = ProductRawMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->whereHas('product_category', function ($query) {
                $query->where('type', '=', 'Raw Materials');
            });
    }
}
