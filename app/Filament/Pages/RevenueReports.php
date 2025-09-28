<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Tables;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Widgets\RevenueStatsCards;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Filament\Widgets\DailyRevenueExpenseTable;
use App\Filament\Widgets\NetProfitCalculationTable;

class RevenueReports extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $title = 'Revenue & Profit Reports';
    protected static string $view = 'filament.pages.revenue-reports';
    protected static ?string $navigationGroup = 'Financial Tracking';
    public $startDate;
    public $endDate;

    public function mount(): void
    {
        // Default filter: today
        $this->startDate = now()->startOfDay()->toDateString();
        $this->endDate   = now()->endOfDay()->toDateString();
    }

        public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    /**
     * Cards at the top
     */
    public function getHeaderWidgets(): array
    {
        return [
            RevenueStatsCards::class,
        ];
    }

public function getFooterWidgets(): array
{
    return [
        DailyRevenueExpenseTable::class,
        NetProfitCalculationTable::class,
    ];
}

    /**
     * Table at the bottom
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(total_amount) as revenue'),
                        DB::raw('COUNT(*) as transactions')
                    )
                    ->groupBy('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->sortable(),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('Total Revenue')
                    ->money('php', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('transactions')
                    ->label('Transactions Count')
                    ->sortable(),
            ])
            ->filters([
                // you can add a date range filter here if needed
            ])
            ->defaultSort('date', 'desc');
    }
}
