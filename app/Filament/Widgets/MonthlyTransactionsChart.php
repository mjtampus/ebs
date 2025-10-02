<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyTransactionsChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Transactions This Year';
    protected static ?string $pollingInterval = '10s'; // e.g. '10s' if you want auto-refresh
    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        $year = now()->year;

        // Group transactions by month
        $monthlyData = Transaction::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('count', 'month');

        // Build an array from Jan to Dec
        $labels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $data[] = $monthlyData[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Transactions Sold',
                    'data' => $data,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // We want a line chart
    }
}
