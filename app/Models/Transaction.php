<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cashier_id',
        'transaction_code',
        'items',
        'total_amount',
        'amount_received',
        'change',
    ];

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change' => 'decimal:2',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function getParsedItemsAttribute(): array
    {
        // Decode JSON string to array
        $itemsArray = json_decode($this->items, true);

        // Return empty array if decoding fails or it's not an array
        if (!$itemsArray || !is_array($itemsArray)) {
            return [];
        }

        // Map the items and return array
        return collect($itemsArray)->map(function ($item) {
            $product = Product::find($item['product_id']);

            return [
                'product_id' => $item['product_id'],
                'product_name' => $product ? $product->name : 'Product Not Found',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'] ?? ($item['quantity'] * $item['unit_price']),
            ];
        })->toArray(); // Important: convert collection to array
    }

}