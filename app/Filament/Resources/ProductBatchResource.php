<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductBatch;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductBatchResource\Pages;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Product Batches';
    protected static ?string $relatedResource = ProductResource::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make() // wrap in a card for better layout
                    ->schema([
                        Forms\Components\Grid::make(2) // 2-column layout
                            ->schema([
                                TextInput::make('batch_number')
                                    ->label('Batch Number')
                                    ->required()
                                    ->helperText('Enter the unique batch number for this product batch.')
                                    ->columnSpan(1),

                                TextInput::make('batch_code')
                                    ->label('Batch Code')
                                    ->disabled()
                                    ->default(fn () => 'BATCH-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT))
                                    ->helperText('Automatically generated code. Cannot be edited.')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->required()
                            ->helperText('Select the date when this batch will expire.')
                            ->displayFormat('F j, Y')
                            ->firstDayOfWeek(1) // Monday as first day
                            ->columnSpan(2),
                    ])
                    ->columns(2) // Card has 2 columns
                    ->columnSpan(2)
                    ->columns(2)
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch_number')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('batch_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->date()
                    ->sortable()
                    ->label('Expires On')
                    ->color(fn ($state) => $state < now() ? 'danger' : 'primary'), // red if expired

                Tables\Columns\IconColumn::make('is_expired')
                    ->boolean()
                    ->label('Expired?')
                    ->colors([
                        'danger' => false,
                        'success' => true,
                    ]),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->label('Created Today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', Carbon::today()))
            ])
            // Clicking the row goes to Manage Products
            ->recordUrl(fn (ProductBatch $record) => static::getUrl('products.index', ['parent' => $record->id]))
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit Batch'),
                Tables\Actions\ViewAction::make()->label('View Batch')
            ])
            ->defaultSort('expiration_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // Add relation manager for products if needed
            // ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductBatches::route('/'),
            'create' => Pages\CreateProductBatch::route('/create'),
            'edit' => Pages\EditProductBatch::route('/{record}/edit'),
            'products.index' => ProductResource\Pages\ListProducts::route('/{parent}/products'),
            'products.create' => ProductResource\Pages\CreateProduct::route('/{parent}/products/create'),
            'products.edit' => ProductResource\Pages\EditProduct::route('/{parent}/products/{record}/edit'),
        ];
    }
}
