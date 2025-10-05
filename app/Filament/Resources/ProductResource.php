<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductStock;
use App\Models\ProductCategory;
use Filament\Resources\Resource;
use App\Models\ProductCategories;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\ProductBatchResource;
use App\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    public static string $parentResource = ProductBatchResource::class;
    protected static ?string $model = Product::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->name;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Product Information')
                ->schema([
                    // Hidden field to store the selected product ID
                    // Hidden::make('product_id')
                    //     ->default(fn ($record) => $record?->id),

                    Select::make('product_id')
                        ->label('Select Product')
                        ->options(Product::with('product_category')->get()->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->reactive() // Disable if editing
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $product = Product::with(['product_stock', 'product_category'])
                                    ->find($state);

                                if ($product) {
                                    $set('product_code', $product->code);
                                    $set('product_name', $product->name);
                                    $set('category', $product->product_category?->type ?? 'N/A');
                                    $set('unit_price', $product->unit_price);
                                    $set('current_stock', $product->product_stock?->stock ?? 0);
                                }
                            }
                        })
                        ->columnSpan(2),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('product_code')
                                ->label('Product Code')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('category')
                                ->label('Category')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('₱'),
                        ]),

                    TextInput::make('current_stock')
                        ->label('Current Stock')
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix('units')
                        ->default(0),
                ])
                ->columns(2),

            Section::make('Add Stock')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('quantity_to_add')
                                ->label('Quantity to Add')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->suffix('units')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $current = (int) $get('current_stock');
                                    $toAdd = (int) $state;
                                    $set('new_total', $current + $toAdd);
                                }),

                            TextInput::make('new_total')
                                ->label('New Total Stock')
                                ->disabled()
                                ->dehydrated(false)
                                ->suffix('units')
                                ->default(0),
                        ]),

                    Textarea::make('notes')
                        ->label('Notes (Optional)')
                        ->rows(3)
                        ->placeholder('Add any notes about this stock addition...'),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Product Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_category.type')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Price')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('product_stock.stock')
                    ->label('Current Stock')
                    ->default('0')
                    ->suffix(fn ($record) => $record->unit)
                    ->sortable()
                    ->color(fn ($state) => match(true) {
                        $state === null || $state <= 10 => 'danger',
                        $state <= 50 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->visible(fn() => auth()->user()?->role === 'admin')
                ->url(
                    fn (Pages\ListProducts $livewire, Model $record): string => static::$parentResource::getUrl('products.edit', [
                        'record' => $record,
                        'parent' => $livewire->parent,
                    ])
                ),
            Tables\Actions\DeleteAction::make()->visible(fn() => auth()->user()?->role === 'admin'),
            Tables\Actions\RestoreAction::make(),
            Tables\Actions\ViewAction::make()->visible(fn() => auth()->user()?->role !== 'admin'),
            Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\Action::make('add_stock')
                    ->label('Add Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantity to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('units'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->action(function (Product $record, array $data) {
                        $stock = $record->product_stock;

                        if ($stock) {
                            $stock->update([
                                'stock' => $stock->stock + $data['quantity'],
                            ]);
                        } else {
                            ProductStock::create([
                                'product_id'   => $record->id,
                                'product_code' => $record->code,
                                'stock'        => $data['quantity'],
                            ]);
                        }
                    })
                    ->successNotificationTitle('Stock added successfully'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            auth()->user()?->role !== 'staff' ? Pages\EditProduct::route('/{record}/edit') : null,
            'stock' => Pages\ProductStock::route('/{record}/stock'),
            'cashier' => Pages\CashierListProducts::route('/cashier'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'cashier';
    }
}
