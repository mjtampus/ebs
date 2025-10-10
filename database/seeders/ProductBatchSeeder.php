<?php

namespace Database\Seeders;

use App\Models\ProductBatch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductBatch::create([
            'product_id' => 1,
            'batch_number' => 'Morning October 10, 2025',
            'batch_code' => 'morning-001',
        ]);

        ProductBatch::create([
            'product_id' => 1,
            'Batch_number' => 'Afternoon October 10, 2025',
            'batch_code' => 'Afternoon-001',
        ]);

        ProductBatch::create([
            'product_id' => 1,
            'Batch_number' => 'Night October 10, 2025',
            'batch_code' => 'Night-001',
        ]);
    }
}
