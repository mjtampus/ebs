<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Product; // Make sure you have this model
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class TopSellingProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Selling Products';
    protected static ?string $pollingInterval = null; // Refresh manually or set e.g. '10s'
    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        // Step 1: Collect all items from transactions
        $transactions = Transaction::pluck('items');

        $productSales = collect();

        foreach ($transactions as $itemsJson) {
            $items = is_array($itemsJson)
                ? $itemsJson
                : json_decode($itemsJson, true);

            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (!isset($item['product_id'], $item['quantity'])) {
                    continue;
                }

                $productSales[$item['product_id']] = 
                    ($productSales[$item['product_id']] ?? 0) + $item['quantity'];
            }
        }

        // Step 2: Sort & take top 5
        $topProducts = collect($productSales)
            ->sortDesc()
            ->take(5);

        // Step 3: Get names
        $labels = [];
        $data = [];

        foreach ($topProducts as $productId => $quantity) {
            $product = Product::find($productId);
            $labels[] = $product?->name ?? 'Unknown Product';
            $data[] = $quantity;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quantity Sold',
                    'data' => $data,
                    'backgroundColor' => [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // Pie chart
    }
}
