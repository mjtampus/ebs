<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ProductCategories;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductCategoriesResource\Pages;

class ProductCategoriesResource extends Resource
{
    protected static ?string $model = ProductCategories::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return ProductCategories::count() > 0 ? (string) ProductCategories::count() : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Category Information')
                    ->description('Enter category details')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('type')
                                ->label('Category Name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('description')
                                ->label('Category Description')
                                ->required()
                                ->maxLength(255),
                        ]),
                        Forms\Components\Toggle::make('has_unit')
                            ->label('Has Unit')
                            ->default(false)
                            ->helperText('Check if this category has units associated with it.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Add category-specific filters here
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
            // Optionally include ProductRelationManager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductCategories::route('/'),
            // 'create' => Pages\CreateProductCategories::route('/create'),
            'edit' => Pages\EditProductCategories::route('/{record}/edit'),
        ];
    }

    public static function canAccess() :bool
    {
        return Auth::user()->role === 'admin';
    }
}
