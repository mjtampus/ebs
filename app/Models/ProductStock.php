<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_batch_id',
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

    public function batch()
    {
        return $this->belongsTo(\App\Models\ProductBatch::class, 'product_batch_id');
    }

    public function sold(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw new \Exception('Insufficient stock available.');
        }

        DB::transaction(function () use ($quantity) {
            $this->decrement('stock', $quantity);

            $this->stockMovements()->create([
                'movement_type' => 'out',
                'product_code'  => $this->product_code,
                'reason' => 'sold',
                'quantity'      => $quantity,
            ]);
        });
    }

    // protected static function booted()
    // {
    //     // When stock is updated
    //     static::updating(function ($stock) {
    //         if ($stock->isDirty('stock')) {
    //             $original = $stock->getOriginal('stock');
    //             $new = $stock->stock;

    //             if ($original !== null && $original !== $new) {
    //                 $stock->stockMovements()->create([
    //                     'movement_type'      => $new > $original ? 'in' : 'out',
    //                     'product_stocks_id'  => $stock->id,
    //                     'product_code'       => $stock->product_code,
    //                     'quantity'           => abs($new - $original),
    //                 ]);
    //             }
    //         }
    //     });

    //     // When stock is first created
    //     static::created(function ($stock) {
    //         if ($stock->stock > 0) {
    //             $stock->stockMovements()->create([
    //                 'movement_type'      => 'in',
    //                 'product_stocks_id'  => $stock->id,
    //                 'product_code'       => $stock->product_code,
    //                 'quantity'           => $stock->stock,
    //             ]);
    //         }
    //     });
    // }

    protected static function booted()
{
    static::saving(function ($stock) {
        if ($stock->stock < 0) {
            throw ValidationException::withMessages([
                'stock' => 'Stock cannot be negative.',
            ]);
        }
    });
}
}
