<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStock extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'product_code',
        'stock',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stockMovements() :HasMany
    {
        return $this->hasMany(StockMovements::class, 'product_stocks_id');
    }
}
