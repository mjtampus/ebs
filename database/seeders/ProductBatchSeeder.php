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
            'batch_number' => 'Morning',
            'batch_code' => 'morning-001',
        ]);

        ProductBatch::create([
            'Batch_number' => 'Afternoon',
            'batch_code' => 'Afternoon-002',
        ]);

        ProductBatch::create([
            'Batch_number' => 'Night',
            'batch_code' => 'Night-002',
        ]);
    }
}
