<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cashier_id',
        'transaction_code',
        'items',
        'total_amount',
        'amount_received',
        'change',
    ];

    protected $casts = [
        'items' => 'array', // Auto-decode items JSON
    ];
}

