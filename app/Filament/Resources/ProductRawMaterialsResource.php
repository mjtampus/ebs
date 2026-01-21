<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductRawMaterialsResource\Pages;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;

class ProductRawMaterialsResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Raw Materials';

    protected static ?string $pluralModelLabel = 'Raw Materials';
    
    protected static ?string $modelLabel = 'Raw Material';

    protected static ?string $navigationGroup = 'Inventory Management';
    
    protected static ?int $navigationSort = 1;

    // Filter to only show raw materials
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('product_category', function ($query) {
                $query->where('type', 'Raw Materials')
                    ->orWhere('type', 'RawMaterials')
                    ->orWhere('type', 'raw materials');
            });
    }

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Basic Information')
                ->description('Enter the basic details of the raw material')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Material Name')
                    ->placeholder('e.g., Organic Flour, Steel Sheets')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (blank($get('code')) && filled($state)) {
                            // Generate base code from name (first 20 chars, uppercased, dash-separated)
                            $base = strtoupper(
                                preg_replace('/[^A-Za-z0-9]+/', '-', trim(substr($state, 0, 20)))
                            );

                            // Append 4 random digits
                            $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

                            $generated = "{$base}-{$random}";

                            $set('code', $generated);
                            $set('product_stock.product_code', $generated);
                        }
                    }),

                TextInput::make('code')
                    ->maxLength(50)
                    ->label('Material Code')
                    ->placeholder('Auto-generated if empty')
                    ->helperText('Leave empty to auto-generate based on material name')
                    ->alphaDash()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Keep product_stock.product_code in sync
                        $set('product_stock.product_code', $state);
                    }),
                        ]),

                    Textarea::make('description')
                        ->maxLength(65535)
                        ->rows(3)
                        ->label('Description')
                        ->placeholder('Provide additional details about this raw material...')
                        ->columnSpanFull()
                        ->required(),
                ])
                ->collapsible()
                ->columns(2),

            Section::make('Classification & Pricing')
                ->description('Categorize and set pricing for the material')
                ->icon('heroicon-o-tag')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Select::make('category_id')
                                ->relationship('product_category', 'type', function ($query) {
                                    $query->where('type', 'Raw Materials')
                                        ->orWhere('type', 'RawMaterials')
                                        ->orWhere('type', 'raw materials');
                                })
                                ->required()
                                ->label('Category')
                                ->searchable()
                                ->preload()
                                ->default(function () {
                                    return \App\Models\ProductCategories::where('type', 'like', '%raw%material%')
                                        ->orWhere('type', 'RawMaterials')
                                        ->first()?->id;
                                })
                                ->native(false),

                            TextInput::make('unit')
                                ->required()
                                ->maxLength(50)
                                ->label('Unit of Measurement')
                                ->placeholder('kg, lbs, L, pcs')
                                ->datalist([
                                    'kg',
                                    'lbs',
                                    'g',
                                    'L',
                                    'ml',
                                    'pcs',
                                    'm',
                                    'cm',
                                    'ft',
                                ])
                                ->helperText('Select or type custom unit'),

                            TextInput::make('unit_price')
                                ->required()
                                ->numeric()
                                ->prefix('₱')
                                ->label('Unit Price')
                                ->placeholder('0.00')
                                ->step(0.01)
                                ->minValue(0),
                        ]),
                ])
                ->collapsible()
                ->columns(1),

            Section::make('Material Image')
                ->description('Upload an image to help identify this material')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('image_path')
                        ->image()
                        ->directory('raw-materials')
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                            '4:3',
                            '16:9',
                        ])
                        ->maxSize(5120)
                        ->label('')
                        ->columnSpanFull()
                        ->imagePreviewHeight('250')
                        ->helperText('Upload an image (Max: 5MB). Recommended: Square format for best display.')
                        ->uploadingMessage('Uploading image...')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('Stock Management')
                ->description('Manage stock quantity for this raw material')
                ->icon('heroicon-o-archive-box')
                ->schema([
                    Forms\Components\Group::make()
                        ->relationship('product_stock')
                        ->schema([
                            TextInput::make('stock')
                                ->label('Available Stock')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->suffix('units')
                                ->helperText('Enter the current quantity in stock.'),

                            TextInput::make('product_code')
                                ->label('Product Code')
                                ->disabled()
                                ->dehydrated()
                        ])->columns(2),
                ])
                ->collapsible(),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-material.png'))
                    ->size(50),

                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Code')
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Code copied!')
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-hashtag')
                    ->color('primary'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Material Name')
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->description(fn (Product $record): string => $record->description 
                        ? \Illuminate\Support\Str::limit($record->description, 50)
                        : ''),

                TextColumn::make('product_category.type')
                    ->badge()
                    ->color('success')
                    ->label('Category')
                    ->icon('heroicon-m-tag')
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-scale'),

                TextColumn::make('unit_price')
                    ->money('PHP')
                    ->sortable()
                    ->label('Unit Price')
                    ->weight(FontWeight::Bold)
                    ->color('warning')
                    ->icon('heroicon-m-currency-dollar'),

                TextColumn::make('product_stock.quantity')
                    ->label('Stock')
                    ->default('0')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ?? '0')
                    ->color(fn ($state) => match (true) {
                        $state === null || $state == 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn ($state) => match (true) {
                        $state === null || $state == 0 => 'heroicon-m-x-circle',
                        $state <= 10 => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->sortable(),

                // TextColumn::make('batch.batch_number')
                //     ->label('Batch')
                //     ->default('—')
                //     ->badge()
                //     ->color('gray')
                //     ->icon('heroicon-m-cube')
                //     ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->label('Added On')
                    ->icon('heroicon-m-calendar')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->label('Last Updated')
                    ->icon('heroicon-m-clock')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                
                SelectFilter::make('category')
                    ->relationship('product_category', 'type')
                    ->label('Category')
                    ->preload()
                    ->multiple(),

                SelectFilter::make('batch')
                    ->relationship('batch', 'batch_number')
                    ->label('Batch')
                    ->preload()
                    ->multiple(),

                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('product_stock', fn ($q) => 
                            $q->where('quantity', '<=', 10)->where('quantity', '>', 0)
                        )
                    )
                    ->toggle(),

                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('product_stock')
                            ->orWhereHas('product_stock', fn ($q) => 
                                $q->where('quantity', '<=', 0)
                            )
                    )
                    ->toggle(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View Details'),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Material'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete Material'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ])
                ->label('Actions'),
            ])
            ->emptyStateHeading('No Raw Materials Yet')
            ->emptyStateDescription('Get started by adding your first raw material to the inventory.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Raw Material')
                    ->icon('heroicon-m-plus'),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProductRawMaterials::route('/'),
            'create' => Pages\CreateProductRawMaterials::route('/create'),
            'edit' => Pages\EditProductRawMaterials::route('/{record}/edit'),
            // 'view' => Pages\ViewProductRawMaterials::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('product_category', function ($query) {
            $query->where('type', 'like', '%raw%material%');
        })->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}