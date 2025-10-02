<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashDrop extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'opening_float_id',
        'amount',
        'date',
        'notes',
        'recorded_by',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
