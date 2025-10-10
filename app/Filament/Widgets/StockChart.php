<?php

namespace App\Filament\Widgets;

use App\Models\ProductStock;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Override;

class StockChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public ?string $filter = 'all_time';

    #[Override]
    public function getHeading(): ?string
    {
        return auth()->user()->role === 'admin'
            ? 'Raw Material Stock Levels'
            : 'Product Stock Levels';
    }

    #[Override]
    public function getDescription(): ?string
    {
        return auth()->user()->role === 'admin'
            ? 'Overview of all raw material stock levels.'
            : 'Overview of finished product stock levels.';
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
        $isAdmin = auth()->user()->role === 'admin';
        $query = ProductStock::query()
            ->with(['product', 'batch'])
            ->whereHas('product.product_category', function ($q) use ($isAdmin) {
                $isAdmin
                    ? $q->where('has_unit', 1) // raw materials
                    : $q->where(fn($qq) => $qq->where('has_unit', 0)->orWhereNull('has_unit')); // finished goods
            });

        // 🕒 Apply filter
        match ($this->filter) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'this_month' => $query
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year),
            default => null,
        };

        $stocks = $query->get();

        // 🔢 Group stock levels
        $groups = [
            'In Stock' => $stocks->filter(fn($s) => $s->stock > 10),
            'Low Stock' => $stocks->filter(fn($s) => $s->stock > 0 && $s->stock <= 10),
            'Out of Stock' => $stocks->filter(fn($s) => $s->stock <= 0),
        ];

        // 🎨 Dynamic colors
        $colors = [
            'In Stock' => '#4CAF50',   // Green
            'Low Stock' => '#FFC107',  // Yellow
            'Out of Stock' => '#F44336', // Red
        ];

        // 🧠 Format labels with limited preview of product names
        $formatNames = function ($collection) {
            $names = $collection->map(function ($s) {
                $product = $s->product->name ?? 'Unnamed';
                $batch = $s->batch->batch_code ?? 'No Batch';
                return "{$product} ({$batch})";
            });

            return $names->take(3)->join(', ') . ($names->count() > 3 ? '...' : '');
        };

        // 📊 Chart dataset
        $labels = [];
        $data = [];
        $bgColors = [];

        foreach ($groups as $label => $items) {
            $labels[] = "{$label}: " . $formatNames($items);
            $data[] = $items->count();
            $bgColors[] = $colors[$label];
        }

        return [
            'datasets' => [[
                'label' => 'Stock Levels',
                'data' => $data,
                'backgroundColor' => $bgColors,
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'hoverOffset' => 12,
            ]],
            'labels' => $labels,
        ];
    }

    #[Override]
    protected function getType(): string
    {
        return 'doughnut';
    }

    #[Override]
    public function getColumnSpan(): int|string|array
    {
        return auth()->user()->role === 'admin' ? 'full' : ($this->columnSpan ?? 1);
    }
}
