<?php

namespace App\Filament\Resources\ProductStockResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductStockResource;
use App\Filament\Resources\ProductStockResource\Widgets\ProductStockWidget;

class ListProductStocks extends ListRecords
{
    protected static string $resource = ProductStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
                ProductStockWidget::class
        ];
    }
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'In Stock' => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->where('stock', '>', 10))
            ->icon('heroicon-o-check-circle'),
            'Low Stock' => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('stock', [1, 10]))
            ->icon('heroicon-o-exclamation-triangle'),            
            'Out of Stock' => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->where('stock', 0))
            ->icon('heroicon-o-x-circle'),
        ];
    }  
}
