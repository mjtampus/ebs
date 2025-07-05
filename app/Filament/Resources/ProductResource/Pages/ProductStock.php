<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class ProductStock extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public static function getNavigationLabel(): string
    {
        return 'Stock';
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
                    ->afterStateHydrated(function (TextInput $component, $state) {
                        // Set initial stock value from the relation
                        $component->state($this->record->product_stock->stock ?? null);
                    }),
            ]),
        ]);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $stockData = $data['product_stock'];
        $stockData['product_code'] = $record->code;

        if ($record->product_stock) {
            $record->product_stock->update($stockData);
        } else {
            $record->product_stock()->create($stockData);
        }

        return $record;
    }

    protected function hasHeader(): bool
    {
        return false;
    }
}
