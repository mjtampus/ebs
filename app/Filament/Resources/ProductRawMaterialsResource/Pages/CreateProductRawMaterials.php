<?php

namespace App\Filament\Resources\ProductRawMaterialsResource\Pages;

use Illuminate\Support\Str;
use App\Models\ProductStock;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductRawMaterialsResource;

class CreateProductRawMaterials extends CreateRecord
{
    protected static string $resource = ProductRawMaterialsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ Generate product code if empty
        if (empty($data['code']) && !empty($data['name'])) {
            $data['code'] = 'RM-' . strtoupper(Str::slug($data['name'], '-')) . '-' . strtoupper(Str::random(4));
        }

        // ✅ Add product_code into nested product_stock
        if (isset($data['product_stock']) && is_array($data['product_stock'])) {
            $data['product_stock']['product_code'] = $data['code'];
        }

        return $data;
    }
}
