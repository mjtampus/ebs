<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductCategory;
use Filament\Resources\Resource;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 2;


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
                        ->required(),

                    Section::make('Stock Information')
                        ->relationship('product_stock')
                        ->schema([
                            TextInput::make('stock')
                                ->label('Stock Quantity')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
                            TextInput::make('product_code')
                                ->label('Product Code')
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                        ]),
                ]),
        ])
        ->columnSpanFull(), // Ensures wizard expands full width inside modal
    ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
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
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('product_category.type')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('info'),

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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            // Define RelationManagers here (e.g., OrdersRelationManager::class)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            // 'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'stock' => Pages\ProductStock::route('/{record}/stock'),
        ];
    }

        public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([

                Pages\EditProduct::class,
                Pages\ProductStock::class

        ]);
    }
}
