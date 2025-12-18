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
            'product_id' => 2,
            'batch_number' => 'Morning October 10, 2025',
            'batch_code' => 'morning-002',
        ]);

        ProductBatch::create([
            'product_id' => 1,
            'batch_number' => 'Morning October 10, 2025',
            'batch_code' => 'Morning-003',
        ]);
    }
}
