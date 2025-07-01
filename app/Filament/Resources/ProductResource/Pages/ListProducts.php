<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $view = 'product-resource.pages.list-products';

    public function getProducts()
    {
        return Product::latest()->get();
    }

    public function isCashier(): bool
    {
        return auth()->check() && auth()->user()->role === 'cashier';
    }
}
