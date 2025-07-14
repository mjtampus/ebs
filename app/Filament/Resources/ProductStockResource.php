<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductStockResource\Pages;
use App\Filament\Resources\ProductStockResource\RelationManagers;
use App\Models\ProductStock;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductStockResource extends Resource
{
    protected static ?string $model = ProductStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?string $navigationLabel = 'Stocks';

    public static function getNavigationBadge(): ?string
    {
        $above = ProductStock::where('stock', '>=', 10)->count();
        $below = ProductStock::where('stock', '<', 10)->count();

        return "↑ $above | ↓ $below";
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
            ->columns([

                Tables\Columns\ImageColumn::make('product.image_path')
                    ->label('Image')
                    ->sortable(), 
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),   
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

                        if ($stock <= 0) {
                            return 'Out of Stock';
                        }

                        if ($stock < 10) {
                            return 'Low Stock';
                        }

                        return 'In Stock';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'Out of Stock' => 'danger',
                            'Low Stock' => 'warning',
                            'In Stock' => 'success',
                            default => 'gray',
                        };
                    })
                    ->sortable(),

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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'create' => Pages\CreateProductStock::route('/create'),
            'edit' => Pages\EditProductStock::route('/{record}/edit'),
        ];
    }
}
