<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
        'name' => 'Spanish Bread',
        'code' => 'Spanish-001',
        'description' => 'A delightful pastry filled with sweet custard and topped with sugar.',
        'image_path' => 'products/01K779VSDA8RY83Y5M0SPFZQND.jpg',
        'category_id' => 2, // Assuming 2 is the ID for 'Bread' category
        'unit_price' => 15,
        'unit' => 'pcs',
        ]);

        Product::create([
        'name' => 'Ensaymada Bread',
        'code' => 'Ensaymada-001',
        'description' => 'A soft, fluffy bread topped with butter, sugar, and grated cheese.',
        'image_path' => '01K77AQR94SH9XF9YZ8NPZMS20.jpg',
        'category_id' => 2, // Assuming 2 is the ID for 'Bread' category
        'unit_price' => 10,
        'unit' => 'pcs',
        ]);
    }
}    
