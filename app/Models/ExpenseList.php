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
        'is_raw',
        'raw_materials_id',
        'quantity','type',
        'unit_price',
        'total_amount'
    ];

    public function product() :BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
