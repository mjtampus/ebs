<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductBatch;

class SoftDeleteExpiredProducts extends Command
{
    protected $signature = 'products:soft-delete-expired';
    protected $description = 'Soft delete products from expired batches';

    public function handle(): void
    {
        $expiredBatches = ProductBatch::whereDate('expiration_date', '<=', now())->get();

        foreach ($expiredBatches as $batch) {
            $batch->products()->delete();
        }

        $this->info('Soft deleted products from expired batches.');
    }
}
