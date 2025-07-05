<?php

namespace App\Models;

use App\Models\ProductCategories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable  = [
        'name',
        'code',
        'description',
        'image_path',
        'category_id',
        'unit_price',
    ];

    public function product_category() :BelongsTo
    {
        return $this->belongsTo(ProductCategories::class , 'category_id');
    }
    public function product_stock()
    {
        return $this->hasOne(ProductStock::class, 'product_id');
    }

    protected static function booted()
    //if the productcode is empty will generate a random code
    {
        static::creating(function ($product) {
            if (empty($product->code)) {
                $prefix = strtoupper(substr($product->name, 0, 3));
                $product->code = $prefix . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
