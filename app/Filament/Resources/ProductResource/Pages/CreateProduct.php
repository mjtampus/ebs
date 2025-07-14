<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $stock = $this->record->product_stock->stock ?? 0;

        if ($stock > 0) {
            $data = [
                'quantity' => $stock,
                'movement_type' => 'in',
                'product_code' => $this->record->code,
            ];

            Log::info('Creating StockMovement:', $data);

            $this->record->product_stock->stockMovements()->create($data);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['unit']) && isset($data['SI'])) {
            $data['unit'] = $data['unit'] . ' ' . $data['SI'];
        } elseif (isset($data['SI'])) {
            $data['unit'] = $data['SI'];
        } else {
            $data['unit'] = 'pcs';
        }

        return $data;
    }
}
