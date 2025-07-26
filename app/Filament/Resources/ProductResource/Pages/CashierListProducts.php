<?php

// namespace App\Filament\Resources\ProductResource\Pages;

// use App\Models\Product;
// use Filament\Resources\Pages\ListRecords;
// use App\Filament\Resources\ProductResource;

// class CashierListProducts extends ListRecords
// {
//     protected static string $resource = ProductResource::class;

//     protected static string $view = 'product-resource.pages.cashier-list-products';


//     public function getProducts()
//     {
//         return Product::latest()->get();
//     }
//     public static function getNavigationLabel(): string
//     {
//         return 'Start New Sales'; 
//     }

//     public function isCashier(): bool
//     {
//         return auth()->user()?->role === 'cashier';
//     }
// }

namespace App\Filament\Resources\ProductResource\Pages;

use App\Models\Product;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;

class CashierListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
    protected static string $view = 'product-resource.pages.cashier-list-products';

    // Public properties accessible in view
    public int $cashierId;
    public string $cashierName;
    public string $cashierRole;

    // Livewire component name
    protected static ?string $component = 'cashier-pos';

    public function mount(): void
    {
        parent::mount();
        
        $user = auth()->user();
        $this->cashierId = $user->id;
        $this->cashierName = $user->name;
        $this->cashierRole = $user->role;
        
        // Dispatch data to frontend
        $this->dispatch('cashierDataLoaded', [
            'id' => $this->cashierId,
            'name' => $this->cashierName,
            'role' => $this->cashierRole
        ]);
    }

    public function getProducts()
    {
        return Product::where('unit', 'pcs')
            ->latest()
            ->get();
    }

    public static function getNavigationLabel(): string
    {
        return 'Start New Sales';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && auth()->user()->role === 'cashier';
    }
}