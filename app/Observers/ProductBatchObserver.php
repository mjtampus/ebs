<?php

namespace App\Observers;

use App\Models\ProductBatch;

class ProductBatchObserver
{
    public function deleted(ProductBatch $batch): void
    {
        // Only handle soft deletes
        if (! $batch->isForceDeleting()) {
            // Delete related product (if needed)
            if ($batch->stocks) {
                $batch->stocks->delete();
            }

            // Delete related stock
            if ($batch->stocks) {
                $batch->stocks->delete();
            }
        }
    }

    public function restored(ProductBatch $batch): void
    {
        if ($batch->products()->withTrashed()->exists()) {
            $batch->products()->withTrashed()->restore();
        }

        if ($batch->stocks()->withTrashed()->exists()) {
            $batch->stocks()->withTrashed()->restore();
        }
    }

    public function forceDeleted(ProductBatch $batch): void
    {
        if ($batch->stocks()->withTrashed()->exists()) {
            $batch->stocks()->withTrashed()->forceDelete();
        }

        if ($batch->products()->withTrashed()->exists()) {
            $batch->products()->withTrashed()->forceDelete();
        }
    }
}
