<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\ExpenseList;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\TableWidget as BaseWidget;

class DailyRevenueExpenseTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Revenue Summary';
    protected static bool $isDiscovered = false;

    protected function getTableFilters(): array
    {
        return [
            'timeframe' => Tables\Filters\SelectFilter::make('timeframe')
                ->label('Timeframe')
                ->options([
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                ])
                ->default('daily'),
        ];
    }

    public function table(Tables\Table $table): Tables\Table
    {
        $timeframe = $this->tableFilters['timeframe'] ?? 'daily';

        if ($timeframe === 'weekly') {
            $groupField = DB::raw('YEARWEEK(created_at, 1) as period');
            $labelField = 'period';
        } elseif ($timeframe === 'monthly') {
            $groupField = DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period');
            $labelField = 'period';
        } else {
            $groupField = DB::raw('DATE(created_at) as period');
            $labelField = 'period';
        }

        $query = Transaction::query()
            ->select(
                $groupField,
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('period')
            ->orderByDesc('period');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make($labelField)
                    ->label(ucfirst($timeframe)),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('Total Revenue')
                    ->money('php'),

                Tables\Columns\TextColumn::make('transactions')
                    ->label('Transaction Count'),
            ])
            ->defaultSort($labelField, 'desc');
    }

    /**
     * Override record key so Filament knows how to uniquely identify each row.
     */
    public function getTableRecordKey(mixed $record): string
    {
        // Our grouped column is 'period'
        return (string) $record->period;
    }
}