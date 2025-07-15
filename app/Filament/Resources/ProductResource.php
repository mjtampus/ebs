<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductCategory;
use Filament\Resources\Resource;
use App\Models\ProductCategories;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Filament\Forms\Components\Tabs\Tab;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Product::count() > 0 ? (string) Product::count() : null;
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                // Step 1: Product Info
                Wizard\Step::make('Product Info')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Product Name')
                                ->required()
                                ->maxLength(255)
                                ->reactive()
                                ->lazy()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $prefix = strtoupper(substr($state, 0, 3));
                                    $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                                    $code = "{$prefix}-{$random}";
                                    $set('code', $code);
                                    $set('product_stock.product_code', $code);
                                }),

                            TextInput::make('code')
                                ->label('Product Code')
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                        ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(500),

                        // Only visible during edit
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('product_category', 'type')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->hidden(fn(string $operation) => $operation === 'create'),

                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->rules(['numeric', 'min:0']),
                    ]),

                // Step 2: Image Upload
                Wizard\Step::make('Image')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Product Image')
                            ->image()
                            ->required()
                            ->directory('products'),
                    ]),

                // Step 3: Category & Stock
                Wizard\Step::make('Category & Stock')
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('product_category', 'type')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive(),

                        Section::make('Unit Details')
                            ->visible(
                                fn($get) =>
                                ProductCategories::find($get('category_id'))?->has_unit === 1
                            )
                            ->schema([
                                Select::make('SI')
                                    ->label('SI Unit')
                                    ->options([
                                        'pcs' => 'Pieces',
                                        'kg' => 'Kilograms',
                                        'ltr' => 'Liters',
                                    ])
                                    ->default('pcs')
                                    ->reactive()
                                    ->afterStateHydrated(function ($component, $state, callable $get, callable $set) {
                                        $unit = $get('unit');
                                        if (preg_match('/\d+\s*(\w+)/', $unit, $matches)) {
                                            $si = strtolower($matches[1]);
                                            if (in_array($si, ['pcs', 'kg', 'ltr'])) {
                                                $set('SI', $si);
                                            }
                                        }
                                    }),

                                TextInput::make('unit')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->visible(fn($get) => filled($get('SI')))
                                    ->afterStateHydrated(function ($component, $state) {
                                        if (preg_match('/^(\d+)/', $state, $matches)) {
                                            $component->state((int) $matches[1]);
                                        }
                                    })
                                    ->dehydrated()
                                    ->reactive()
                                    ->suffix(fn($get) => $get('SI') ?? '')
                                    ->helperText('Enter the quantity and select the SI unit. This will be combined with the SI unit. For example, "10 pcs" or "5 kg".')
                                    ->extraAttributes(['inputmode' => 'numeric']),
                            ]),


                        Section::make('Stock Information')
                            ->relationship('product_stock')
                            ->schema([
                                TextInput::make('stock')
                                    ->label('Stock Quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->hidden(fn(string $operation) => $operation === 'edit'),

                                TextInput::make('product_code')
                                    ->label('Product Code')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),
            ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->url(fn($record) => asset('storage/' . $record->image_path))
                    ->getStateUsing(fn($record) => asset('storage/' . $record->image_path))
                    ->square()
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->description),

                Tables\Columns\TextColumn::make('product_category.type')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                // Tables\Columns\TextColumn::make('product_stock.stock')
                //     ->label('Stock')
                //     ->sortable()
                //     ->numeric()
                //     ->color(fn ($state) => $state < 10 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->sortable()
                    ->money('PHP', true)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable()
                    ->badge()
                    ->color('secondary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('product_category', 'type')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),    
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn() => auth()->user()?->role !== 'staff'),
                Tables\Actions\DeleteAction::make()->visible(fn() => auth()->user()?->role !== 'staff'),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                ]),
            ]);
    }
            public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->withTrashed();
        }

    public static function getRelations(): array
    {
        return [
            // Define RelationManagers here (e.g., OrdersRelationManager::class)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            auth()->user()?->role !== 'staff' ? Pages\EditProduct::route('/{record}/edit') : null,
            'stock' => Pages\ProductStock::route('/{record}/stock'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $items = [];

        if (auth()->user()?->role !== 'staff') {
            $items[] = Pages\EditProduct::class;
        }

        $items[] = Pages\ProductStock::class;

        return $page->generateNavigationItems($items);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    // public static function canEdit(): bool
    // {
    //     return auth()->user()?->role === 'admin';
    // }
}