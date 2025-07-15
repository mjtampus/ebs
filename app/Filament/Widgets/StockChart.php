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
    
        return [
            'datasets' => [
                [
                    'label' => 'Stock',
                    'data' => $stocks->pluck('stock')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#C9CBCF', '#FF6384', '#36A2EB', '#FFCE56',
                    ],
                ],
            ],
            'labels' => $stocks->map(fn($stock) => $stock->product->name ?? 'Unnamed')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
