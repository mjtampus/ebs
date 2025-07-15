<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $productStock = $product->product_stock;

        if ($productStock) {
            $productStock->stockMovements()->delete();
            $productStock->delete();
        }
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        // Restore product stock (soft deleted) if it exists
        $productStock = $product->product_stock()->withTrashed()->first();

        if ($productStock) {
            $productStock->restore();

            // Restore related stock movements
            $productStock->stockMovements()->withTrashed()->restore();
        }
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        // Optionally, you can also force delete the product stock if needed
        $product->product_stock()->forceDelete();
        $product->product_stock()->stockMovements()->forceDelete();
    }
}
