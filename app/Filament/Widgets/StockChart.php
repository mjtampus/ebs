<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use App\Models\ProductStock;
use Override;

class StockChart extends ChartWidget
{
    public ?string $filter = 'today';
    protected static ?int $sort = 2;
    #[Override]
    public function getHeading(): ?string
    {
        return auth()->user()->role === 'admin' 
            ? 'Raw material stock levels' 
            : 'Product stock levels';
    }
    #[Override]
    public function getDescription(): ?string
    {
        return auth()->user()->role === 'admin' 
            ? 'All stock raw material levels' 
            : 'All Product stock levels';
    }
    #[Override]
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'this_month' => 'This Month',
            'all_time' => 'All Time',
        ];
    }
    #[Override]
    protected function getData(): array
    {
        $filter = $this->filter;
        $isAdmin = auth()->user()->role === 'admin';
    
        $stocksQuery = ProductStock::query()
            ->whereHas('product.product_category', function ($query) use ($isAdmin) {
                $query->where('has_unit', $isAdmin ? 1 : 0);
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
