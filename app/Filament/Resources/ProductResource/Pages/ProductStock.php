<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProductResource;

class ProductStock extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public static function getNavigationLabel(): string
    {
        return 'Add Stock';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Stock Information')->schema([
                TextInput::make('stock')
                    ->label('Current Stock')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->disabled()
                    ->afterStateHydrated(function (TextInput $component, $state) {
                        $component->state($this->record->product_stock->stock ?? null);
                    }),
            ]),
        Section::make('Add Stock')->schema([
            TextInput::make('add_stock')
                ->label('Add Stock')
                ->numeric()
                ->minValue(1)
                ->required(),
        ]),            
        ]);
    }

protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
{
    $addStock = (int) ($data['add_stock'] ?? 0);
    $productStock = $record->product_stock;

    if ($addStock > 0 && $productStock) {
        $newStock = $productStock->stock + $addStock;

        $productStock->stockMovements()->create([
            'product_code' => $record->code,
            'quantity' => $addStock,
            'movement_type' => 'in',
        ]);

        $productStock->update([
            'stock' => $newStock,
            'product_code' => $record->code,
        ]);

        $record->load('product_stock');
    }

    return $record;
}
protected function afterSave(): void
{
    $this->form->fill([
        'stock' => $this->record->product_stock->stock,
        'add_stock' => null,
    ]);
}
    protected function hasHeader(): bool
    {
        return false;
    }
}
