<?php

namespace App\Observers;

use App\Models\ProductBatch;

class ProductBatchObserver
{
    /**
     * Handle the ProductBatch "deleted" event.
     */
    public function deleted(ProductBatch $batch): void
    {
        // If this is a soft delete (not force delete)
        if (! $batch->isForceDeleting()) {
            $batch->products()->each(function ($product) {
                // Soft delete each product
                $product->delete();

                // Soft delete related product stock if exists
                if ($product->product_stock) {
                    $product->product_stock->delete();
                }
            });
        }
    }

    /**
     * Handle the ProductBatch "restored" event.
     */
    public function restored(ProductBatch $batch): void
    {
        // Restore products and their stocks
        $batch->products()->withTrashed()->each(function ($product) {
            $product->restore();

            if ($product->product_stock()->withTrashed()->exists()) {
                $product->product_stock()->withTrashed()->restore();
            }
        });
    }

    /**
     * Handle the ProductBatch "force deleted" event.
     */
    public function forceDeleted(ProductBatch $batch): void
    {
        $batch->products()->withTrashed()->each(function ($product) {
            // Force delete product stock
            if ($product->product_stock()->withTrashed()->exists()) {
                $product->product_stock()->withTrashed()->forceDelete();
            }

            // Force delete the product itself
            $product->forceDelete();
        });
    }
}
