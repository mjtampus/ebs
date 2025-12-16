<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'batch_number', 'batch_code', 'quantity', 'expiration_date'];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function products(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stocks(): HasOne
    {
        return $this->hasOne(ProductStock::class, 'product_batch_id');
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    protected static function booted()
    {
        // Auto-generate batch_code on creating
        static::creating(function ($batch) {
            if (empty($batch->batch_code)) {
                $prefix = 'BATCH'; // customize as needed
                $batch->batch_code = $prefix . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        });

        static::creating(function ($batch) {
            if (empty($batch->batch_number)) {
                // Get the product's name from the related Product model
                $productName = $batch->products ? $batch->products->name : 'default';  // Handle case if product is not set

                // Generate the batch code with product's name as prefix
                $batch->batch_number = $productName . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        });

        // Soft delete all related products if batch expired when updated
        // static::updated(function ($batch) {
        //     if ($batch->expiration_date && $batch->expiration_date->isPast()) {
        //         $batch->products()->delete();
        //     }
        // });
    }
}
