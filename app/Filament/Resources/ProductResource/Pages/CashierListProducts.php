<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Models\Product;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;

class CashierListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $view = 'product-resource.pages.cashier-list-products';


    public function getProducts()
    {
        return Product::latest()->get();
    }
    public static function getNavigationLabel(): string
    {
        return 'Start New Sales'; 
    }

    public function isCashier(): bool
    {
        return auth()->user()?->role === 'cashier';
    }
}
