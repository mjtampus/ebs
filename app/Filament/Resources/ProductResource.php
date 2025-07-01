<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Facades\Filament;
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 2;
    public static function getNavigationGroup(): ?string
    {
        return Filament::auth()->user()?->role === 'admin'
            ? static::$navigationGroup
            : 'Products';
    }

    public static function getNavigationLabel(): string
    {
        return Filament::auth()->user()?->role === 'admin'
            ? static::$navigationLabel
            : 'Start New Sale';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product Information')
                    ->description('Enter basic details of the product.')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Product Name')
                                ->reactive()
                                ->lazy()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $prefix = strtoupper(substr($state, 0, 3));
                                    $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                                    $set('code', "{$prefix}-{$random}");
                                }),

                            Forms\Components\TextInput::make('code')
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated()
                                ->label('Product Code'),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(500),

                        Forms\Components\Select::make('category_id')
                            ->relationship('product_category', 'type')
                            ->label('Category')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->label('Product Image')
                            ->required()
                            ->directory('products'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
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
                    ->tooltip(fn($record) => $record->description),

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
                Tables\Actions\EditAction::make()->visible($isAdmin),
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
        ];
    }
}
