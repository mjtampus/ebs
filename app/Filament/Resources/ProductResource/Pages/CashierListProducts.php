<?php

// app/Filament/Resources/ProductResource/Pages/CashierListProducts.php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Models\Product;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Cashier\Resources\CashierProductResource; // <-- this is key

class CashierListProducts extends ListRecords
{
    protected static string $resource = CashierProductResource::class; // <-- changed

    protected static string $view = 'product-resource.pages.cashier-list-products';

    public function getProducts()
    {
        return Product::latest()->get();
    }
}
