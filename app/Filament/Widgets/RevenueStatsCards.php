<?php

namespace App\Filament\Widgets;

use App\Models\ExpenseList;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RevenueStatsCards extends BaseWidget
{
    protected ?string $heading = 'Revenue Overview';
    protected static bool $isDiscovered = false;

    protected function getCards(): array
    {
        $today = now()->toDateString();

        // Daily totals
        $transaction = Transaction::whereDate('created_at', $today)->sum('total_amount');
        $expenselist = ExpenseList::whereDate('created_at', $today)->sum('total_amount');
        $todayRevenue = $transaction - $expenselist;
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();

        // Yesterday's comparison
        $yesterday = now()->subDay()->toDateString();
        $yesterdayTransaction = Transaction::whereDate('created_at', $yesterday)->sum('total_amount');
        $yesterdayExpense = ExpenseList::whereDate('created_at', $yesterday)->sum('total_amount');
        $yesterdayRevenue = $yesterdayTransaction - $yesterdayExpense;
        $yesterdayTransactions = Transaction::whereDate('created_at', $yesterday)->count();

        // Calculate percentage changes
        $revenueChange = $yesterdayRevenue > 0
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
            : 0;
        $transactionChange = $yesterdayTransactions > 0
            ? (($todayTransactions - $yesterdayTransactions) / $yesterdayTransactions) * 100
            : 0;

        // Average sales per transaction today
        $averageSales = $todayTransactions > 0 ? $transaction / $todayTransactions : 0;
        $yesterdayAvgSales = $yesterdayTransactions > 0 ? $yesterdayTransaction / $yesterdayTransactions : 0;
        $avgSalesChange = $yesterdayAvgSales > 0
            ? (($averageSales - $yesterdayAvgSales) / $yesterdayAvgSales) * 100
            : 0;

        // Peak sales hour today
        $peakHour = Transaction::select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', $today)
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderByDesc('count')
            ->first();
        $peakHourDisplay = $peakHour ? sprintf('%02d:00', $peakHour->hour) : 'N/A';
        $peakHourCount = $peakHour ? $peakHour->count : 0;

        // Weekly revenue (current week vs last week)
        $weekRevenue = Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount');
        $weekExpense = ExpenseList::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount');
        $weekNetRevenue = $weekRevenue - $weekExpense;

        $lastWeekRevenue = Transaction::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->sum('total_amount');
        $lastWeekExpense = ExpenseList::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->sum('total_amount');
        $lastWeekNetRevenue = $lastWeekRevenue - $lastWeekExpense;

        $weekChange = $lastWeekNetRevenue > 0
            ? (($weekNetRevenue - $lastWeekNetRevenue) / $lastWeekNetRevenue) * 100
            : 0;

        // Monthly revenue (current month vs last month)
        $monthRevenue = Transaction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        $monthExpense = ExpenseList::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        $monthNetRevenue = $monthRevenue - $monthExpense;

        $lastMonthRevenue = Transaction::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');
        $lastMonthExpense = ExpenseList::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');
        $lastMonthNetRevenue = $lastMonthRevenue - $lastMonthExpense;

        $monthChange = $lastMonthNetRevenue > 0
            ? (($monthNetRevenue - $lastMonthNetRevenue) / $lastMonthNetRevenue) * 100
            : 0;

        return [
            Stat::make('Total Revenue Today', '₱' . number_format($todayRevenue, 2))
                ->description($todayRevenue >= 0
                    ? ($revenueChange >= 0
                        ? number_format(abs($revenueChange), 1) . '% increase from yesterday'
                        : number_format(abs($revenueChange), 1) . '% decrease from yesterday')
                    : 'Operating at a loss today')
                ->descriptionIcon($todayRevenue >= 0
                    ? ($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    : 'heroicon-m-exclamation-triangle')
                ->color($todayRevenue >= 0
                    ? ($revenueChange >= 0 ? 'success' : 'warning')
                    : 'danger')
                ->icon('heroicon-o-banknotes')
                ->chart($this->getRevenueSparkline()),

            Stat::make('Number of Transactions', number_format($todayTransactions))
                ->description($todayTransactions > 0
                    ? ($transactionChange >= 0
                        ? number_format(abs($transactionChange), 1) . '% more than yesterday'
                        : number_format(abs($transactionChange), 1) . '% less than yesterday')
                    : 'No transactions recorded today')
                ->descriptionIcon($todayTransactions > 0
                    ? ($transactionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    : 'heroicon-m-information-circle')
                ->color($transactionChange >= 0 ? 'success' : 'warning')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Average Sales per Transaction', '₱' . number_format($averageSales, 2))
                ->description($todayTransactions > 0
                    ? ($avgSalesChange >= 0
                        ? number_format(abs($avgSalesChange), 1) . '% higher than yesterday'
                        : number_format(abs($avgSalesChange), 1) . '% lower than yesterday')
                    : 'No transaction data available')
                ->descriptionIcon($todayTransactions > 0
                    ? ($avgSalesChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    : 'heroicon-m-minus-circle')
                ->color($avgSalesChange >= 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Peak Sales Hour', $peakHourDisplay)
                ->description($peakHour
                    ? $peakHourCount . ' transaction' . ($peakHourCount != 1 ? 's' : '') . ' at peak hour'
                    : 'No peak hour data today')
                ->descriptionIcon($peakHour ? 'heroicon-m-fire' : 'heroicon-m-information-circle')
                ->color($peakHour ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),

            Stat::make('Weekly Revenue', '₱' . number_format($weekNetRevenue, 2))
                ->description($weekNetRevenue >= 0
                    ? ($weekChange >= 0
                        ? number_format(abs($weekChange), 1) . '% increase from last week'
                        : number_format(abs($weekChange), 1) . '% decrease from last week')
                    : 'Weekly expenses exceed revenue')
                ->descriptionIcon($weekNetRevenue >= 0
                    ? ($weekChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    : 'heroicon-m-exclamation-triangle')
                ->color($weekNetRevenue >= 0
                    ? ($weekChange >= 0 ? 'info' : 'warning')
                    : 'danger')
                ->icon('heroicon-o-calendar'),

            Stat::make('Monthly Revenue', '₱' . number_format($monthNetRevenue, 2))
                ->description($monthNetRevenue >= 0
                    ? ($monthChange >= 0
                        ? number_format(abs($monthChange), 1) . '% increase from last month'
                        : number_format(abs($monthChange), 1) . '% decrease from last month')
                    : 'Monthly expenses exceed revenue')
                ->descriptionIcon($monthNetRevenue >= 0
                    ? ($monthChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    : 'heroicon-m-exclamation-triangle')
                ->color($monthNetRevenue >= 0
                    ? ($monthChange >= 0 ? 'success' : 'warning')
                    : 'danger')
                ->icon('heroicon-o-calendar-days'),
        ];
    }

    /**
     * Get sparkline data for revenue trend (last 7 days)
     */
    protected function getRevenueSparkline(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $revenue = Transaction::whereDate('created_at', $date)->sum('total_amount');
            $expense = ExpenseList::whereDate('created_at', $date)->sum('total_amount');
            $data[] = $revenue - $expense;
        }
        return $data;
    }
}
