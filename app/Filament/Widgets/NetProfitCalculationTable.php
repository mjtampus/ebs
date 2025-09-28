<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\ExpenseList;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class NetProfitCalculationTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Net Profit Calculation';
    protected static bool $isDiscovered = false;

    public function table(Tables\Table $table): Tables\Table
    {
        $transactions = Transaction::query()
            ->select(
                DB::raw('DATE(created_at) as timeframe'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('timeframe')
            ->orderByDesc('timeframe');

        return $table
            ->query($transactions)
            ->columns([
                Tables\Columns\TextColumn::make('timeframe')
                    ->label('Date')
                    ->date(),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('Revenue')
                    ->money('php'),

                Tables\Columns\TextColumn::make('expenses')
                    ->label('Expenses')
                    ->money('php')
                    ->getStateUsing(function ($record) {
                        return ExpenseList::whereDate('expense_date', $record->timeframe)
                            ->sum('total_amount');
                    }),

                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Net Profit')
                    ->money('php')
                    ->getStateUsing(function ($record) {
                        $revenue = $record->revenue;
                        $expenses = ExpenseList::whereDate('expense_date', $record->timeframe)
                            ->sum('total_amount');
                        return $revenue - $expenses;
                    }),

                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Profit Margin %')
                    ->getStateUsing(function ($record) {
                        $revenue = $record->revenue;
                        $expenses = ExpenseList::whereDate('expense_date', $record->timeframe)
                            ->sum('total_amount');
                        $netProfit = $revenue - $expenses;

                        return $revenue > 0
                            ? number_format(($netProfit / $revenue) * 100, 2) . '%'
                            : '0%';
                    }),
            ])
            ->defaultSort('timeframe', 'desc');
    }

    /**
     * Let Filament know how to uniquely identify each row.
     */
    public function getTableRecordKey(mixed $record): string
    {
        // Use timeframe (our grouped column) as the unique key
        return (string) $record->timeframe;
    }
}
