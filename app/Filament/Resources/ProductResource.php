<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?string $navigationGroup = 'Bakery';

    protected static ?string $navigationLabel = 'Breads';

    protected static ?string $modelLabel = 'Bread';

    protected static ?string $pluralModelLabel = 'Breads';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('product_category') // eager-load category to avoid N+1
            ->whereHas('product_category', function (Builder $query) {
                $query->where('type', 'Bread');
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bread Details')
                    ->description('Basic information about your bread product')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Bread Name')
                            ->placeholder('e.g., Whole Wheat Loaf')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('Product Code')
                            ->placeholder('Leave empty to auto-generate.')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Describe your bread product (ingredients, taste, etc.)')
                            ->columnSpanFull()
                            ->rows(4),
                        Forms\Components\TextInput::make('category_id')
                            ->hidden()

                    ])->columns(2),

                Forms\Components\Section::make('Bread Image')
                    ->description('Upload a mouth-watering photo of your bread')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth(500)
                            ->imageResizeTargetHeight(500)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pricing & Unit')
                    ->description('Set the price and unit of measurement')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Price')
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->required()
                            ->step(0.01),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->default('pcs')
                            ->placeholder('e.g., pcs, loaf, pack')
                            ->maxLength(255)
                            ->required(),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Photo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Bread Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn (Product $record): string => $record->description ?? ''),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Price')
                    ->numeric()
                    ->sortable()
                    ->prefix('₱')
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch_count')
                    ->label('Batches')
                    ->counts('batch')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                ->label('Add Stock / Edit Bread'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Bread Information')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_path')
                            ->label(''),
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Bread Name')
                                    ->size('lg')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('code')
                                    ->label('Product Code')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('Price')
                                    ->prefix('₱')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('unit')
                                    ->label('Unit'),
                            ])->columns(2),
                    ])->columns(2),
                Infolists\Components\Section::make('Batches')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('batch')
                            ->schema([
                                Infolists\Components\TextEntry::make('batch_number')
                                    ->label('Batch Number'),
                                Infolists\Components\TextEntry::make('batch_code')
                                    ->label('Batch Code')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                            ])->columns(3),
                    ]),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ProductResource\RelationManagers\ProductBatchesRelationManager::class,
            \App\Filament\Resources\ProductResource\RelationManagers\ProductStocksRelationManager::class, // ✅ add this
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'cashier' => Pages\CashierListProducts::route('/cashier'), // 👈 add this

        ];
    }

    public static function canAccess() :bool
    {
        return Auth::user()->role === 'cashier';
    }
}