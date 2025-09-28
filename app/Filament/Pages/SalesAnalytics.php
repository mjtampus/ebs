<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\SalesAnalyticsOverview;
use App\Filament\Widgets\TopSellingProductsChart;
use App\Filament\Widgets\MonthlyTransactionsChart;

class SalesAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.sales-analytics';
    protected static ?string $title = 'Sales Analytics';
    protected static ?string $navigationGroup = 'Financial Tracking';

    public function getHeaderWidgets(): array
    {
        return [
            SalesAnalyticsOverview::class,
            TopSellingProductsChart::class,
            MonthlyTransactionsChart::class
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
