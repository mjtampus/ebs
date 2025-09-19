<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Models\Transaction;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Widgets\TotalSales;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    public $customFilterData = [];

public function getTabs(): array
{
    $user = Auth::user();

    return [
        'all' => Tab::make('All Transactions')
            ->badge(fn () => $user->role === 'cashier'
                ? Transaction::where('cashier_id', $user->id)->count()
                : Transaction::count()),

        'today' => Tab::make('Today')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
            ->badge(fn () => $user->role === 'cashier'
                ? Transaction::where('cashier_id', $user->id)
                             ->whereDate('created_at', today())
                             ->count()
                : Transaction::whereDate('created_at', today())->count()),

        'this_week' => Tab::make('This Week')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]))
            ->badge(fn () => $user->role === 'cashier'
                ? Transaction::where('cashier_id', $user->id)
                             ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                             ->count()
                : Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                             ->count()),

        'this_month' => Tab::make('This Month')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year))
            ->badge(fn () => $user->role === 'cashier'
                ? Transaction::where('cashier_id', $user->id)
                             ->whereMonth('created_at', now()->month)
                             ->whereYear('created_at', now()->year)
                             ->count()
                : Transaction::whereMonth('created_at', now()->month)
                             ->whereYear('created_at', now()->year)
                             ->count()),

        'this_year' => Tab::make('This Year')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereYear('created_at', now()->year))
            ->badge(fn () => $user->role === 'cashier'
                ? Transaction::where('cashier_id', $user->id)
                             ->whereYear('created_at', now()->year)
                             ->count()
                : Transaction::whereYear('created_at', now()->year)
                             ->count()),
    ];
}

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filterByDate')
                ->label('Advanced Filter')
                ->icon('heroicon-o-funnel')
                ->color('info')
                ->form([
                    Grid::make(2)
                        ->schema([
                            Select::make('filter_type')
                                ->label('Filter Type')
                                ->options([
                                    'month_year' => 'Month & Year',
                                    'specific_week' => 'Specific Week',
                                    'date_range' => 'Date Range',
                                    'year_only' => 'Year Only',
                                ])
                                ->default('month_year')
                                ->reactive()
                                ->required(),

                            Select::make('month')
                                ->label('Month')
                                ->options([
                                    1 => 'January',
                                    2 => 'February',
                                    3 => 'March',
                                    4 => 'April',
                                    5 => 'May',
                                    6 => 'June',
                                    7 => 'July',
                                    8 => 'August',
                                    9 => 'September',
                                    10 => 'October',
                                    11 => 'November',
                                    12 => 'December',
                                ])
                                ->default(now()->month)
                                ->visible(fn ($get) => in_array($get('filter_type'), ['month_year'])),

                            Select::make('year')
                                ->label('Year')
                                ->options(function () {
                                    $years = [];
                                    $startYear = Transaction::oldest()->first()?->created_at?->year ?? now()->year;
                                    $endYear = now()->year;

                                    for ($year = $endYear; $year >= $startYear; $year--) {
                                        $years[$year] = $year;
                                    }
                                    return $years;
                                })
                                ->default(now()->year)
                                ->visible(fn ($get) => in_array($get('filter_type'), ['month_year', 'year_only']))
                                ->required(fn ($get) => in_array($get('filter_type'), ['month_year', 'year_only'])),

                            Select::make('week')
                                ->label('Week of Year')
                                ->options(function () {
                                    $weeks = [];
                                    for ($week = 1; $week <= 53; $week++) {
                                        $startOfWeek = now()->setISODate(now()->year, $week)->startOfWeek();
                                        $endOfWeek = now()->setISODate(now()->year, $week)->endOfWeek();
                                        $weeks[$week] = "Week {$week} ({$startOfWeek->format('M d')} - {$endOfWeek->format('M d')})";
                                    }
                                    return $weeks;
                                })
                                ->default(now()->week)
                                ->visible(fn ($get) => $get('filter_type') === 'specific_week'),

                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->visible(fn ($get) => $get('filter_type') === 'date_range')
                                ->required(fn ($get) => $get('filter_type') === 'date_range'),

                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->visible(fn ($get) => $get('filter_type') === 'date_range')
                                ->required(fn ($get) => $get('filter_type') === 'date_range')
                                ->after('start_date'),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->applyCustomFilter($data);
                }),

            Action::make('clearFilter')
                ->label('Clear Filter')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(function () {
                    $this->clearCustomFilter();
                })
                ->visible(fn () => $this->hasCustomFilter()),
        ];
    }

    protected function applyCustomFilter(array $data): void
    {
        $this->customFilterData = $data;

        // Set filter label for display
        $filterLabel = $this->getFilterLabel($data);
        session(['transaction_filter_label' => $filterLabel]);

        // Refresh the table to apply the filter
        $this->resetTable();
    }

    protected function getFilterLabel(array $data): string
    {
        $filterType = $data['filter_type'];

        switch ($filterType) {
            case 'month_year':
                return Carbon::createFromDate($data['year'], $data['month'])->format('F Y');

            case 'specific_week':
                return "Week {$data['week']} of " . now()->year;

            case 'date_range':
                return Carbon::parse($data['start_date'])->format('M d, Y') . ' - ' . Carbon::parse($data['end_date'])->format('M d, Y');

            case 'year_only':
                return "Year {$data['year']}";

            default:
                return 'Custom Filter';
        }
    }

    protected function clearCustomFilter(): void
    {
        $this->customFilterData = [];
        session()->forget('transaction_filter_label');
        $this->resetTable();
    }

    protected function hasCustomFilter(): bool
    {
        return !empty($this->customFilterData) || session()->has('transaction_filter_label');
    }

    public function getTitle(): string
    {
        $baseTitle = 'Transactions';
        $filterLabel = session('transaction_filter_label');

        return $filterLabel ? "{$baseTitle} - {$filterLabel}" : $baseTitle;
    }

    // Override the table query to apply custom filters
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // 1️⃣ Apply role-based filter first
        if (Auth::user()->role === 'cashier') {
            $query->where('cashier_id', Auth::id());
        }

        // 2️⃣ Apply any custom filters if they exist
        if (!empty($this->customFilterData)) {
            $query = $this->applyFilterToQuery($query, $this->customFilterData);
        }

        return $query;
    }

    protected function applyFilterToQuery(Builder $query, array $data): Builder
    {
        $filterType = $data['filter_type'];

        switch ($filterType) {
            case 'month_year':
                return $query->whereMonth('created_at', $data['month'])
                           ->whereYear('created_at', $data['year']);

            case 'specific_week':
                $startOfWeek = now()->setISODate($data['year'] ?? now()->year, $data['week'])->startOfWeek();
                $endOfWeek = now()->setISODate($data['year'] ?? now()->year, $data['week'])->endOfWeek();

                return $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);

            case 'date_range':
                return $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay()
                ]);

            case 'year_only':
                return $query->whereYear('created_at', $data['year']);

            default:
                return $query;
        }
    }

    // Reset custom filter when switching tabs
    public function updatedActiveTab(): void
    {
        if ($this->hasCustomFilter()) {
            $this->clearCustomFilter();
        }
    }

    // Mount method to persist filter across page loads
    public function mount(): void
    {
        parent::mount();

        // Restore custom filter from session if it exists
        if (session()->has('transaction_custom_filter')) {
            $this->customFilterData = session('transaction_custom_filter');
        }
    }

    // Save custom filter to session
    protected function saveCustomFilterToSession(): void
    {
        if (!empty($this->customFilterData)) {
            session(['transaction_custom_filter' => $this->customFilterData]);
        } else {
            session()->forget('transaction_custom_filter');
        }
    }

    // Quick action methods for common filters
    public function quickFilterToday(): void
    {
        $this->applyCustomFilter([
            'filter_type' => 'date_range',
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]);
    }

    public function quickFilterThisWeek(): void
    {
        $this->applyCustomFilter([
            'filter_type' => 'specific_week',
            'week' => now()->week,
        ]);
    }

    public function quickFilterThisMonth(): void
    {
        $this->applyCustomFilter([
            'filter_type' => 'month_year',
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

        protected function getHeaderWidgets(): array
    {
        return [
            TotalSales::class,
        ];
    }


}
