<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Models\Product;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Cashier\Resources\CashierProductResource;

class CashierListProducts extends ListRecords
{
    protected static string $resource = CashierProductResource::class;

    protected static string $view = 'product-resource.pages.cashier-list-products';

    public function getProducts()
    {
        return Product::latest()->get();
    }

    // ✅ Add this method to fix the Blade error
    public function isCashier(): bool
    {
        return auth()->user()?->role === 'cashier';
    }
}
