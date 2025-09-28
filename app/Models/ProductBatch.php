<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
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
        // Auto-generate batch_code
        static::creating(function ($batch) {
            if (empty($batch->batch_code)) {
                $prefix = 'BATCH'; // You can customize this
                $batch->batch_code = $prefix . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

}
