<?php

namespace App\Filament\Cashier\Resources;

use App\Models\Product;
use Filament\Resources\Resource;
use App\Filament\Resources\ProductResource\Pages\CashierListProducts;
class CashierProductResource extends Resource
{
    protected static ?string $model = Product::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => CashierListProducts::route('/'),
        ];
    }
}
