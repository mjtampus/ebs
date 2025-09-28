<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductBatchResource\Traits\HasParentResource;

class CreateProduct extends CreateRecord
{
    use HasParentResource;
    protected static string $resource = ProductResource::class;

    protected ?int $pBatchId = null;

    public function mount(): void
    {
        parent::mount();

        // pull from querystring
        $this->pBatchId = request()->query('p_batch_id');
    }

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
        if (!empty($data['unit'])) {
            if (isset($data['SI']) && $data['SI'] === 'custom' && !empty($data['custom_unit'])) {
                $data['unit'] = $data['unit'] . ' ' . $data['custom_unit'];
            } elseif (isset($data['SI'])) {
                $data['unit'] = $data['unit'] . ' ' . $data['SI'];
            } else {
                $data['unit'] = $data['unit'] . ' pcs';
            }
        } else {
            if (isset($data['SI']) && $data['SI'] === 'custom' && !empty($data['custom_unit'])) {
                $data['unit'] = $data['custom_unit'];
            } elseif (isset($data['SI'])) {
                $data['unit'] = $data['SI'];
            } else {
                $data['unit'] = 'pcs';
            }
        }

        $data[$this->getParentRelationshipKey()] = $this->parent->id;


        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getParentResource()::getUrl('products.index', [
            'parent' => $this->parent,
        ]);
    }

}
