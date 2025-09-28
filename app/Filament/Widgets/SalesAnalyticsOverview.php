<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class SalesAnalyticsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected function getCards(): array
    {
        $totalTransactions = Transaction::count();
        $totalRevenue = Transaction::sum('total_amount');
        $averageSpend = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        return [
            Card::make('Number of Transactions', number_format($totalTransactions))
                ->description('Total transactions recorded')
                ->icon('heroicon-o-shopping-cart'),

            Card::make('Total Revenue', '₱' . number_format($totalRevenue, 2))
                ->description('Sum of all transaction totals')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Card::make('Average Customer Spend', '₱' . number_format($averageSpend, 2))
                ->description('Average spend per transaction')
                ->color('primary')
                ->icon('heroicon-o-user-group'),
        ];
    }
}
