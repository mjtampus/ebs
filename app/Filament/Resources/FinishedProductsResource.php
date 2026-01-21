<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FinishedProducts;
use Filament\Resources\Resource;
use App\Models\ProductCategories;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FinishedProductsResource\Pages;
use App\Filament\Resources\FinishedProductsResource\RelationManagers;

class FinishedProductsResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $modelLabel = 'Finished Product';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->description('Enter the basic information about the product')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->reactive()
                            ->lazy()
                            ->placeholder('e.g., Pandesal, Ensaymada')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $prefix = strtoupper(substr($state, 0, 3));
                                $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                                $code = "{$prefix}-{$random}";
                                $set('code', $code);
                            }),

                        Forms\Components\TextInput::make('code')
                            ->label('Product Code')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-generated based on product name')
                            ->prefixIcon('heroicon-o-hashtag'),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(
                                ProductCategories::where('type', 'Bread')
                                    ->pluck('type', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-tag'),

                        Forms\Components\Select::make('unit')
                            ->label('Unit of Measurement')
                            ->required()
                            ->options([
                                'pcs' => 'Pieces',
                                'kg' => 'Kilograms',
                                'ltr' => 'Liters',
                                'custom' => 'Custom',
                            ])
                            ->default('pcs')
                            ->prefixIcon('heroicon-o-scale'),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('0.00')
                            ->helperText('Price per unit'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Additional Details')
                    ->description('Optional information and product image')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Product Description')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Enter detailed description of the product...'),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Product Image')
                            ->image()
                            ->imageEditor()
                            ->directory('products')
                            ->columnSpanFull()
                            ->imagePreviewHeight('200')
                            ->helperText('Maximum file size: 2MB. Recommended: 800x800px'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->size(50),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Code copied!')
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('product_category.type')
                    ->label('Category')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color('info'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Price')
                    ->money('PHP')
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->color('success'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pcs' => 'Pieces',
                        'kg' => 'Kilograms',
                        'ltr' => 'Liters',
                        'custom' => 'Custom',
                        default => $state,
                    }),

                // Tables\Columns\TextColumn::make('productBatch.batch_number')
                //     ->label('Batch')
                //     ->searchable()
                //     ->sortable()
                //     ->toggleable()
                //     ->placeholder('No batch assigned')
                //     ->icon('heroicon-o-cube'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-clock'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(
                        ProductCategories::where('type', 'Bread')
                            ->pluck('type', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Category'),

                Tables\Filters\SelectFilter::make('unit')
                    ->label('Unit')
                    ->options([
                        'pcs' => 'Pieces',
                        'kg' => 'Kilograms',
                        'ltr' => 'Liters',
                        'custom' => 'Custom',
                    ])
                    ->multiple()
                    ->indicator('Unit'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->placeholder('Select start date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->emptyStateHeading('No finished products yet')
            ->emptyStateDescription('Create your first finished product to get started.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Finished Product')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->whereHas('product_category', function (Builder $query) {
    //             $query->where('type', 'Bread');
    //         });
    // }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canAccess() :bool
    {
        return Auth::user()->role === 'admin';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinishedProducts::route('/'),
            'create' => Pages\CreateFinishedProducts::route('/create'),
            'edit' => Pages\EditFinishedProducts::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('product_category', function (Builder $query) {
            $query->where('type', 'Bread');
        })->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
