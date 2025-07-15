<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovements extends Model
{
    use SoftDeletes;
    protected $table = 'stock_movements';
    
    protected $fillable = [
        'movement_type',
        'product_stocks_id',
        'product_code',
        'quantity',
    ];

    public function productStock() :BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }
}
