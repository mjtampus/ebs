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
}
