<?php

namespace App\Filament\Resources\ProductStockResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductStockResource;
use App\Filament\Resources\ProductStockResource\Widgets\ProductStockWidget;
use Illuminate\Support\Facades\DB;

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
            ->modifyQueryUsing(fn (Builder $query) => $query->havingRaw('SUM(stock) > 10'))
            ->icon('heroicon-o-check-circle'),
            'Low Stock' => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->havingRaw('SUM(stock) BETWEEN 1 AND 10'))
            ->icon('heroicon-o-exclamation-triangle'),
            'Out of Stock' => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->havingRaw('SUM(stock) = 0'))
            ->icon('heroicon-o-x-circle'),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->join('product_batches', 'product_stocks.product_batch_id', '=', 'product_batches.id')
            ->select([
                'product_stocks.product_id',
                'product_stocks.product_code',
                DB::raw('SUM(product_stocks.stock) as stock'),
                DB::raw('MIN(product_stocks.id) as id'),
                DB::raw('GROUP_CONCAT(product_batches.batch_number SEPARATOR ", ") as batch_names'),
                // If you want to show stock per batch:
                DB::raw('GROUP_CONCAT(product_stocks.stock SEPARATOR ", ") as batch_details'),
            ])
            ->groupBy('product_stocks.product_id', 'product_stocks.product_code')
            ->orderBy('product_stocks.product_id', 'asc');
    }
}
