<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TotalSales extends BaseWidget
{
    protected function getCards(): array
    {
        $query = Transaction::query();

        // If the user is a cashier, only count their own transactions
        if (Auth::user()->role === 'cashier') {
            $query->where('cashier_id', Auth::id());
        }

        return [
            Card::make('Total Sales', 'PHP ' . number_format($query->sum('total_amount'), 2))
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Card::make('Transactions Processed', $query->count())
                ->color('primary')
                ->icon('heroicon-o-receipt-percent'),
        ];
    }
}
