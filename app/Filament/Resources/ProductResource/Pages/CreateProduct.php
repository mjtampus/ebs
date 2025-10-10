<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductBatchResource\Traits\HasParentResource;

class CreateProduct extends CreateRecord
{
     protected static string $resource = ProductResource::class;


     protected function mutateFormDataBeforeCreate(array $data): array
     {
        // Auto-generate product code if not provided
        $data['category_id'] = 2;

        return $data;
     }

}
