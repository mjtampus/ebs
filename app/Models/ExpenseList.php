<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseList extends Model
{
    use SoftDeletes;
    
    protected $table = 'expense_lists';

    protected $fillable = [ 
        'expense_name',
        'description',
        'product_id',
        'quantity',
        'type',
        'unit_price',
        'total_amount',
        'expense_date',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}