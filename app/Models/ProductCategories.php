<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategories extends Model
{
    protected $table = 'product_categories';

    protected $fillable = [
        'type',
        'description',
    ];

    public function products() :HasMany
    {
        return $this->HasMany(Product::class);
    }
}
