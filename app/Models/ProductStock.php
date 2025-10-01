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

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovements::class, 'product_stocks_id');
    }

    protected static function booted()
    {
        // When stock is updated
        static::updating(function ($stock) {
            if ($stock->isDirty('stock')) {
                $original = $stock->getOriginal('stock');
                $new = $stock->stock;

                if ($original !== null && $original !== $new) {
                    $stock->stockMovements()->create([
                        'movement_type'      => $new > $original ? 'in' : 'out',
                        'product_stocks_id'  => $stock->id,
                        'product_code'       => $stock->product_code,
                        'quantity'           => abs($new - $original),
                    ]);
                }
            }
        });

        // When stock is first created
        static::created(function ($stock) {
            if ($stock->stock > 0) {
                $stock->stockMovements()->create([
                    'movement_type'      => 'in',
                    'product_stocks_id'  => $stock->id,
                    'product_code'       => $stock->product_code,
                    'quantity'           => $stock->stock,
                ]);
            }
        });
    }
}
