<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StockMovements;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\Summarizers\Summarizer;
use App\Filament\Resources\StockMovementsResource\Pages;

class StockMovementsResource extends Resource
{
    protected static ?string $model = StockMovements::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'product_code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Movement Details')
                    ->schema([
                        Forms\Components\Select::make('movement_type')
                            ->label('Movement Type')
                            ->options([
                                'in' => 'Stock In',
                                'out' => 'Stock Out',
                                'adjustment' => 'Adjustment',
                                'transfer' => 'Transfer',
                                'return' => 'Return',
                            ])
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                $set('reason', match($state) {
                                    'in' => 'Purchase Order',
                                    'out' => 'Sales Order',
                                    'adjustment' => 'Stock Count Correction',
                                    'transfer' => 'Inter-warehouse Transfer',
                                    'return' => 'Customer Return',
                                    default => null,
                                })
                            )
                            ->columnSpan(1),

                        Forms\Components\Select::make('product_stocks_id')
                            ->label('Product')
                            ->relationship('productStock.product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = \App\Models\ProductStock::find($state);
                                    if ($product) {
                                        $set('product_code', $product->product_code);
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('product_code')
                            ->label('Product Code')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->step(1)
                            ->suffix('units')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason / Notes')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Movement Date')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('movement_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                        'warning' => 'adjustment',
                        'info' => 'transfer',
                        'primary' => 'return',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                        'adjustment' => 'Adjustment',
                        'transfer' => 'Transfer',
                        'return' => 'Return',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_code')
                    ->label('Product Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Code copied')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('productStock.product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('productStock.batch.batch_number')
                    ->label('Product Batch')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->productStock->batch->batch_number ?? 'N/A'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->movement_type === 'out' ? '-' : '+') . number_format($state)
                    )
                    ->color(fn ($record) => match ($record->movement_type) {
                        'in', 'return' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    })
                    ->summarize([
                        Summarizer::make()
                            ->label('Net Stock by Batch')
                            ->using(function ($query) {
                                // Group by batch and calculate net for each batch
                                $results = $query
                                    ->selectRaw('
                                        product_stocks_id,
                                        SUM(CASE
                                            WHEN movement_type IN ("in", "return") THEN quantity
                                            WHEN movement_type = "out" THEN -quantity
                                            ELSE 0
                                        END) as net_quantity
                                    ')
                                    ->groupBy('product_stocks_id')
                                    ->get();

                                // Format results by batch with additional details
                                $batches = [];
                                $totalNet = 0;

                                foreach ($results as $result) {
                                    $productStock = \App\Models\ProductStock::find($result->product_stocks_id);
                                    if ($productStock && $productStock->batch) {
                                        $batchNumber = $productStock->batch->batch_number;
                                        $net = $result->net_quantity;

                                        if (!isset($batches[$batchNumber])) {
                                            $batches[$batchNumber] = 0;
                                        }

                                        $batches[$batchNumber] += $net;
                                        $totalNet += $net;
                                    }
                                }

                                // Sort batches by net quantity (descending)
                                arsort($batches);

                                return [
                                    'batches' => $batches,
                                    'total' => $totalNet,
                                    'count' => count($batches)
                                ];
                            })
                            ->formatStateUsing(function ($state) {
                                if (empty($state['batches'])) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<span class="text-gray-500 italic">No batch movements recorded</span>'
                                    );
                                }

                                $batches = $state['batches'];
                                $total = $state['total'];
                                $count = $state['count'];

                                // Build formatted output
                                $batchLines = [];
                                foreach ($batches as $batch => $net) {
                                    $color = $net > 0 ? 'text-green-600' : ($net < 0 ? 'text-red-600' : 'text-gray-600');
                                    $sign = $net > 0 ? '+' : '';
                                    $icon = $net > 0 ? '↑' : ($net < 0 ? '↓' : '→');

                                    $batchLines[] = sprintf(
                                        '<span class="inline-flex items-center gap-1"><strong>%s:</strong> <span class="%s font-semibold">%s%s %s</span></span>',
                                        $batch,
                                        $color,
                                        $icon,
                                        $sign,
                                        number_format($net)
                                    );
                                }

                                $totalColor = $total > 0 ? 'text-green-700 bg-green-50' : ($total < 0 ? 'text-red-700 bg-red-50' : 'text-gray-700 bg-gray-50');
                                $totalSign = $total > 0 ? '+' : '';

                                $summary = sprintf(
                                    '<div class="space-y-2"><div class="flex flex-wrap gap-x-4 gap-y-1">%s</div><div class="mt-3 pt-2 border-t"><span class="inline-flex items-center px-2 py-1 rounded text-sm font-semibold %s">Total Net: %s%s units across %d batch%s</span></div></div>',
                                    implode('', $batchLines),
                                    $totalColor,
                                    $totalSign,
                                    number_format($total),
                                    $count,
                                    $count !== 1 ? 'es' : ''
                                );

                                return new \Illuminate\Support\HtmlString($summary);
                            })
                        ]),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable()
                    ->limit(40)
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->reason),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                    ]),

                SelectFilter::make('product_stocks_id')
                    ->label('Product')
                    ->relationship('productStock.product', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\Product::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orderBy('name')
                            ->limit(50)
                            ->pluck('name', 'id');
                    })
                    ->preload()
                    ->placeholder('All Products'),


                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['to'] ?? null) {
                            $indicators['to'] = 'Until ' . \Carbon\Carbon::parse($data['to'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            // ->filtersLayout(FiltersLayout::AboveContentCollapsible
            // )
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected'),
                ]),
            ])
            ->emptyStateHeading('No stock movements yet')
            ->emptyStateDescription('Once you create stock movements, they will appear here.')
            ->emptyStateIcon('heroicon-o-arrow-path');
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
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovements::route('/create'),
            // 'view' => Pages\ViewStockMovements::route('/{record}'),
            // 'edit' => Pages\EditStockMovements::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->role === 'admin';
    }
}
