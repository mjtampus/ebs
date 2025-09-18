<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductBatchResource\Traits\HasParentResource;

class ListProducts extends ListRecords
{
    use HasParentResource;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(
                    fn (): string => static::getParentResource()::getUrl('products.create', [
                        'parent' => $this->parent,
                    ])
                ),
        ];
    }
}
