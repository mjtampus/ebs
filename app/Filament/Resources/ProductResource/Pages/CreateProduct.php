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
    use HasParentResource;
    protected static string $resource = ProductResource::class;

    protected ?int $pBatchId = null;

    public function mount(): void
    {
        parent::mount();

        // pull from querystring
        $this->pBatchId = request()->query('p_batch_id');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $productId     = $data['product_id'];
        $quantityToAdd = $data['quantity_to_add'];

        $product = Product::with('product_stock')->find($productId);

        if ($product) {
            // Update product with batch id
            $product->update([
                'product_batch_id' => $this->parent->id,
            ]);

            $stock = $product->product_stock;

            if ($stock) {
                // Update existing stock
                $stock->increment('stock', $quantityToAdd);
            } else {
                // Create new stock record
                ProductStock::create([
                    'product_code'      => $product->code,
                    'product_id'        => $product->id,
                    'product_batch_id'  => $this->parent->id,
                    'stock'             => $quantityToAdd,
                ]);
            }
        }

        // Ensure Filament knows the relation
        $data[$this->getParentRelationshipKey()] = $this->parent->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Override to prevent actual creation
        // Return the product that was updated instead
        return Product::find($data['product_id']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getParentResource()::getUrl('products.index', [
            'parent' => $this->parent,
        ]);
    }

}
