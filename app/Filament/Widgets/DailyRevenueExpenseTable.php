<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\ExpenseList;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class DailyRevenueExpenseTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Daily Revenue & Expenses';

    public function table(Tables\Table $table): Tables\Table
    {
        $query = Transaction::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderByDesc('date');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('Date'),
                Tables\Columns\TextColumn::make('revenue')->label('Revenue')->money('php'),
                Tables\Columns\TextColumn::make('transactions')->label('Transactions'),
                Tables\Columns\TextColumn::make('expenses')
                    ->label('Expenses')
                    ->money('php')
                    ->getStateUsing(function ($record) {
                        return ExpenseList::whereDate('expense_date', $record->date)->sum('total_amount');
                    }),
            ]);
    }
}
