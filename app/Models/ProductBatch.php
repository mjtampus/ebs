<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBatch extends Model
{
    use SoftDeletes;

    protected $fillable = ['batch_number', 'batch_code', 'quantity', 'expiration_date'];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
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

        // Soft delete all related products if batch expired when updated
        // static::updated(function ($batch) {
        //     if ($batch->expiration_date && $batch->expiration_date->isPast()) {
        //         $batch->products()->delete();
        //     }
        // });
    }
}
