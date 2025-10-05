<?php

namespace App\Filament\Resources\FinishedProductsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\FinishedProductsResource;

class ListFinishedProducts extends ListRecords
{
    protected static string $resource = FinishedProductsResource::class;

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
                $query->where('type', '=', 'Bread');
            });
    }
}
