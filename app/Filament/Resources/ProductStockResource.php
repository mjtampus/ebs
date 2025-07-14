<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductStock;
use App\Models\StockMovements;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductStockResource\Pages;
use App\Filament\Resources\ProductStockResource\RelationManagers;

class ProductStockResource extends Resource
{
    protected static ?string $model = ProductStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Stocks';

    public static function getNavigationBadge(): ?string
    {
        $above = ProductStock::where('stock', '>=', 10)->count();
        $below = ProductStock::where('stock', '<', 10)->count();

        return "↑ $above | $below ↓";
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_id')
                    ->label('Product ID')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('product_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('create_stock')
                    ->label('Add product stock')
                    ->modalHeading('Create New Product Stock')
                    ->form([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('product_id')
                                ->label('Select Product')
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $product = \App\Models\Product::find($state);
                                    $set('product_code', $product?->code ?? null);
                                    $set('product_image', $product?->image_path ?? null);
                                }),

                            Forms\Components\TextInput::make('product_code')
                                ->label('Product Code')
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->maxLength(255),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\Select::make('movement_type')
                                ->label('Movement Type')
                                ->options([
                                    'in' => 'In',
                                    'out' => 'Out',
                                ])
                                ->default('in')
                                ->required(),

                            Forms\Components\TextInput::make('stock')
                                ->label('Stock Quantity')
                                ->required()
                                ->numeric()
                                ->default(0),
                        ])->columns(2),

                        Forms\Components\Placeholder::make('product_image_preview')
                            ->label('Product Image')
                            ->content(function ($get) {
                                $image = $get('product_image');

                                return $image
                                    ? new HtmlString('<div class="py-2"><img src="' . asset('storage/' . $image) . '" class="w-64 rounded-xl shadow" /></div>')
                                    : new HtmlString('');
                            })
                            ->visible(fn ($get) => filled($get('product_image')))
                            ->columnSpanFull()
                            ->disableLabel(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        return $data;
                    })
                    ->action(function (array $data, Action $action): void {
                        $stockRecord = ProductStock::firstOrNew([
                            'product_id' => $data['product_id'],
                        ]);

                        $change = $data['movement_type'] === 'out'
                            ? -$data['stock']
                            : $data['stock'];

                        $stockRecord->product_code = $data['product_code'];
                        $stockRecord->stock = ($stockRecord->exists ? $stockRecord->stock : 0) + $change;
                        $stockRecord->save(); 

                        StockMovements::create([
                            'product_id'         => $data['product_id'],
                            'product_stocks_id'  => $stockRecord->id,
                            'product_code'       => $data['product_code'],
                            'movement_type'      => $data['movement_type'],
                            'quantity'           => $data['stock'],
                        ]);
                        $action->success();
                    })
                    ->successNotificationTitle('Stock Updated Successfully')
                    ->failureNotificationTitle('Failed to Update Stock')
                    ->color('primary'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('product.image_path')
                    ->label('Image'),

                Tables\Columns\TextColumn::make('product.name')
                    ->numeric(),

                Tables\Columns\TextColumn::make('product_code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.product_category.type')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->color(fn ($state) => $state < 10 ? 'danger' : 'success')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('restock_status')
                    ->label('Restock Status')
                    ->getStateUsing(function ($record) {
                        $stock = $record->stock;

                        if ($stock <= 0) return 'Out of Stock';
                        if ($stock < 10) return 'Low Stock';
                        return 'In Stock';
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Out of Stock' => 'danger',
                        'Low Stock' => 'warning',
                        'In Stock' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_category')
                    ->label('Category')
                    ->relationship('product.product_category', 'type')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'in_stock' => 'In Stock',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        
                        return match ($value) {
                            'out_of_stock' => $query->where('stock', '<=', 0),
                            'low_stock'    => $query->where('stock', '>', 0)->where('stock', '<', 10),
                            'in_stock'     => $query->where('stock', '>=', 10),
                            default        => $query,
                        };
                    }),
            ])
                    ->actions([
                        Tables\Actions\EditAction::make()
                            ->label('Add Stock')
                            ->modalHeading('Edit Product Stock')
                            ->successNotificationTitle('stock updated successfully')
                            ->failureNotificationTitle('stock update failed')
                            ->form(function () {
                                return [
                                    Forms\Components\Group::make([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->disabled()
                                            ->required(),

                                        Forms\Components\TextInput::make('product_code')
                                            ->label('Product Code')
                                            ->required(),   
                                    ])->columns(2),

                                    Forms\Components\Group::make([
                                        Forms\Components\Select::make('movement_type')
                                            ->label('Movement Type')
                                            ->options([
                                                'in' => 'In',
                                                'out' => 'Out',
                                            ])
                                            ->default('in')
                                            ->helperText('Select the type of stock movement you want to perform. in for adding stock, out for removing stock.')
                                            ->required(),

                                        Forms\Components\TextInput::make('stock')
                                            ->label('Adjust Quantity')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Enter Quantity to add or remove')
                                            ->required(),
                                    ])->columns(2),

                                    Forms\Components\Placeholder::make('product_image_preview')
                                        ->label('Product Image')
                                        ->content(function ($get, $state, $record) {
                                            $image = $record?->product?->image_path;

                                            return $image
                                                ? new \Illuminate\Support\HtmlString(
                                                    '<div class="flex justify-center py-4">
                                                        <img src="' . asset('storage/' . $image) . '" class="w-52 rounded-xl shadow border" />
                                                    </div>'
                                                )
                                                : new \Illuminate\Support\HtmlString(
                                                    '<div class="text-sm text-gray-500 italic text-center">No image available</div>'
                                                );
                                        })
                                        ->visible(fn ($record) => filled($record?->product?->image_path))
                                        ->columnSpanFull()
                                        ->disableLabel(),
                                ];
                            })
                            ->action(function (array $data, \App\Models\ProductStock $record, Action $action) {
                                $originalStock = $record->stock;
                                
                                if ($data['movement_type'] === 'in') {
                                    $record->stock += $data['stock'];
                                } elseif ($data['movement_type'] === 'out') {
                                    $record->stock = max(0, $record->stock - $data['stock']);
                                }

                                $record->product_code = $data['product_code'];
                                $record->save();

                                \App\Models\StockMovements::create([
                                    'product_id'         => $record->product_id,
                                    'product_stocks_id'  => $record->id,
                                    'product_code'       => $record->product_code,
                                    'movement_type'      => $data['movement_type'],
                                    'quantity'           => $data['stock'],
                                ]);
                                $action->success();
                            })
                    ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductStocks::route('/'),
            // 'create' => Pages\CreateProductStock::route('/create'),
            // 'edit' => Pages\EditProductStock::route('/{record}/edit'),
        ];
    }
}
