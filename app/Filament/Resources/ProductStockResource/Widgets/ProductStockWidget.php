<?php

namespace App\Filament\Resources\ProductStockResource\Widgets;

use App\Models\ProductStock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStockWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalStock = ProductStock::sum('stock');
    
        $lowStockCount = ProductStock::where('stock', '<=', 10)->count();

        $OutofStockCount = ProductStock::where('stock', '==', 0)->count();
    
        $stockTrends = \App\Models\StockMovements::query()
            ->selectRaw("
                DATE(created_at) as date,
                SUM(CASE 
                    WHEN movement_type = 'in' THEN quantity 
                    WHEN movement_type = 'out' THEN -quantity 
                    ELSE 0 
                END) as total
            ")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');
    
        $chartData = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartData->push($stockTrends[$date] ?? 0);
        }
    
        return [
            Stat::make('Total Items in Stock', number_format($totalStock))
                ->icon('heroicon-o-archive-box')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->chart($chartData->toArray())
                ->description('Total number of items in stock')
                ->chartColor('info')
                ->color('success'),
    
            Stat::make('Low Stock Items', $lowStockCount)
                ->icon('heroicon-o-exclamation-circle')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->chart([100, 35, 50, 75, 30, 20, 20])
                ->description('Stocks below 10')
                ->color('warning'),

            Stat::make('Out Of Stock', $OutofStockCount)
                ->icon('heroicon-o-exclamation-triangle')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->chart([100, 35, 50, 75, 30, 20, 20])
                ->description('Stocks needed to be restocked')
                ->color('danger'),    
        ];
    }
    
    
}