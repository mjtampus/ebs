<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use App\Models\ProductStock;

class StockChart extends ChartWidget
{
    protected static ?string $heading = 'Product Stock Chart';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'this_month' => 'This Month',
            'all_time' => 'All Time',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;
    
        $stocksQuery = ProductStock::query()
            ->whereHas('product.product_category', function ($query) {
                $query->where('has_unit', 1);
            })
            ->with('product');
    
        if ($filter === 'today') {
            $stocksQuery->whereDate('created_at', now());
        } elseif ($filter === 'this_month') {
            $stocksQuery->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
        }
    
        $stocks = $stocksQuery->get();
    
        $inStockProducts = $stocks->filter(fn($s) => $s->stock > 10);
        $lowStockProducts = $stocks->filter(fn($s) => $s->stock > 0 && $s->stock <= 10);
        $outOfStockProducts = $stocks->filter(fn($s) => $s->stock <= 0);
    
        $inStockCount = $inStockProducts->count();
        $lowStockCount = $lowStockProducts->count();
        $outOfStockCount = $outOfStockProducts->count();
    
        // Prepare label with product names
        $formatNames = fn($products) => $products
            ->map(fn($p) => $p->product->name ?? 'Unnamed')
            ->join(', ') ?: 'None';
    
        return [
            'datasets' => [
                [
                    'label' => 'Stock Levels',
                    'data' => [$inStockCount, $lowStockCount, $outOfStockCount],
                    'backgroundColor' => ['#4CAF50', '#FFC107', '#F44336'],
                ],
            ],
            'labels' => [
                'In Stock: ' . $formatNames($inStockProducts),
                'Low Stock: ' . $formatNames($lowStockProducts),
                'Out of Stock: ' . $formatNames($outOfStockProducts),
            ],
        ];
    }    

    protected function getType(): string
    {
        return 'doughnut';
    }
}
