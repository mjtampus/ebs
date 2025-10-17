<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'cashier' => [
                'id' => $this->cashier_id,
                'name' => optional($this->cashier)->name,
            ],
            'items' => json_decode($this->items, true),
            'total_amount' => (float) $this->total_amount,
            'amount_received' => (float) $this->amount_received,
            'change' => (float) $this->change,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
