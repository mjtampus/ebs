<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class RevenueStatsCards extends BaseWidget
{
    protected ?string $heading = 'Revenue Overview';
    protected static bool $isDiscovered = false;

    protected function getCards(): array
    {
        $today = now()->toDateString();

        // Daily totals
        $todayRevenue = Transaction::whereDate('created_at', $today)->sum('total_amount');
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();

        // Average sales per transaction today
        $averageSales = $todayTransactions > 0 ? $todayRevenue / $todayTransactions : 0;

        // Peak sales hour today (hour with max transactions)
        $peakHour = Transaction::select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', $today)
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderByDesc('count')
            ->first();
        $peakHourDisplay = $peakHour ? $peakHour->hour . ':00' : 'N/A';

        // Weekly revenue (last 7 days)
        $weekRevenue = Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount');

        // Monthly revenue (this month)
        $monthRevenue = Transaction::whereMonth('created_at', now()->month)->sum('total_amount');

        return [
            Card::make('Total Revenue Today', '₱' . number_format($todayRevenue, 2))
                ->description('Revenue collected today')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Card::make('Number of Transactions', number_format($todayTransactions))
                ->description('Transactions today')
                ->icon('heroicon-o-shopping-cart'),

            Card::make('Average Sales per Transaction', '₱' . number_format($averageSales, 2))
                ->description('Today’s average transaction')
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),

            Card::make('Peak Sales Hour', $peakHourDisplay)
                ->description('Hour with highest transactions today')
                ->icon('heroicon-o-clock'),

            Card::make('Weekly Revenue', '₱' . number_format($weekRevenue, 2))
                ->description('This week')
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Card::make('Monthly Revenue', '₱' . number_format($monthRevenue, 2))
                ->description('This month')
                ->color('warning')
                ->icon('heroicon-o-calendar-days'),
        ];
    }
}
