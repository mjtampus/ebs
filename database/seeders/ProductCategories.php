<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategories as Categories;

class ProductCategories extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Categories::create([
            'type' => 'Raw materials',
            'description' => 'Raw Material',
            'has_unit' => true,
        ]);

        Categories::create([
            'type' => 'Bread',
            'description' => 'Finished Product',
            'has_unit' => false,
        ]);
    }
}
