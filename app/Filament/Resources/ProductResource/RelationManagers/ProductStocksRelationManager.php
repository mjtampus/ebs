<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\ProductStock;
use App\Models\ProductBatch;
use App\Models\StockMovements;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class ProductStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'product_stock'; // Assuming Product hasMany ProductStock

    protected static ?string $recordTitleAttribute = 'product_code';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_batch_id')
                    ->label('Batch')
                    ->relationship('batch', 'batch_number')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ])
                    ->default('in')
                    ->required(),

                Forms\Components\TextInput::make('stock')
                    ->label('Quantity')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Current Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state === 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y'),
            ])
            ->headerActions([
                Action::make('add_stock')
                    ->label('Add / Adjust Stock')
                    ->slideOver()
                    ->form([
                        Forms\Components\Select::make('product_batch_id')
                            ->label('Batch')
                            ->options(fn (RelationManager $livewire) =>
                                \App\Models\ProductBatch::with('products')
                                    ->where('product_id', $livewire->ownerRecord->id)
                                    ->get()
                                    ->mapWithKeys(function ($batch) {
                                        return [
                                            $batch->id => "{$batch->products->name} - {$batch->batch_number} - {$batch->batch_code}",
                                        ];
                                    })
                            )
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->helperText('Only shows batches for the selected product.'),

                        Forms\Components\Select::make('movement_type')
                            ->label('Movement Type')
                            ->options([
                                'in' => 'In',
                                'out' => 'Out',
                            ])
                            ->default('in')
                            ->required(),

                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->label('Quantity')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->rows(13)

                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $product = $livewire->ownerRecord;

                        $product->load('product_stock');

                        $stockRecord = ProductStock::firstOrNew([
                            'product_id' => $product->id,
                            'product_code' => $product->code,
                            'product_batch_id' => $data['product_batch_id'],
                        ]);

                        $change = $data['movement_type'] === 'out'
                            ? -$data['stock']
                            : $data['stock'];

                        $stockRecord->stock = ($stockRecord->exists ? $stockRecord->stock : 0) + $change;
                        $stockRecord->save();

                        StockMovements::create([
                            'product_id' => $product->id,
                            'product_code' => $product->code,
                            'product_stocks_id' => $stockRecord->id,
                            'product_batch_id' => $data['product_batch_id'],
                            'movement_type' => $data['movement_type'],
                            'reason' => $data['reason'],
                            'quantity' => $data['stock'],
                        ]);
                    })
                    ->successNotificationTitle('Stock updated successfully'),
            ]);
    }
}
