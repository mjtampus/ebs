<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductBatch;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\ProductResource;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductBatchResource\Pages;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductBatchResource\RelationManagers;
use App\Filament\Resources\ProductBatchResource\RelationManagers\ProductsRelationManager;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $relatedResource = ProductResource::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('batch_number'),
            Forms\Components\TextInput::make('batch_code')->required(),
            Forms\Components\DatePicker::make('expiration_date'),
        ]);
    }

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->name;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('batch_number'),
            Tables\Columns\TextColumn::make('batch_code'),
            // Tables\Columns\TextColumn::make('quantity'),
            Tables\Columns\TextColumn::make('expiration_date')->date(),
            Tables\Columns\IconColumn::make('is_expired')
                ->boolean()
                ->label('Expired?'),
        ])
        ->filters([
            Tables\Filters\Filter::make('today')
                ->label('Expiring Today')
                ->query(fn (Builder $query) => $query->whereDate('expiration_date', Carbon::today()))
                ->default(), // ✅ This makes it applied by default
        ])
        ->actions([
            Action::make('Manage Products')
            ->color('success')
            ->icon('heroicon-m-academic-cap')
            ->url(
                fn (ProductBatch $record): string => static::getUrl('products.index', [
                    'parent' => $record->id,
                ])
            ),]);
    }


    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductBatches::route('/'),
            'create' => Pages\CreateProductBatch::route('/create'),
            'edit' => Pages\EditProductBatch::route('/{record}/edit'),
                   // Lessons
            'products.index' => ListProducts::route('/{parent}/products'),
            'products.create' => CreateProduct::route('/{parent}/products/create'),
            'products.edit' => EditProduct::route('/{parent}/products/{record}/edit'),
               ];
    }
}
