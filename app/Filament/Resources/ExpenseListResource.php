<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use App\Models\ExpenseList;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\ExpenseListResource\Pages;
use Filament\Forms\Components\Section;

class ExpenseListResource extends Resource
{
    protected static ?string $model = ExpenseList::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Financial Tracking';
    protected static ?string $recordTitleAttribute = 'expense_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Expense Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Expense Category')
                            ->options([
                                'Raw Material' => 'Raw Material',
                                'Utility' => 'Utility',
                                'Labor' => 'Labor',
                                'Equipment' => 'Equipment',
                                'Marketing' => 'Marketing',
                                'Transportation' => 'Transportation',
                                'Other' => 'Other',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear dependent fields when type changes
                                $set('product_id', null);
                                $set('expense_name', '');
                                $set('unit_price', 0);
                                $set('total_amount', 0);
                            }),

                        Forms\Components\Select::make('product_id')
                            ->label('Raw Material')
                            ->relationship(
                                'product',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas('product_category', fn ($q) => $q->where('has_unit', 1))
                            )
                            ->visible(fn (callable $get) => $get('type') === 'Raw Material')
                            ->required(fn (callable $get) => $get('type') === 'Raw Material')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $product = Product::with('product_category')->find($state);
                                    if ($product) {
                                        $set('unit_price', $product->unit_price ?? 0);
                                        $set('expense_name', $product->name);
                                        // Recalculate total when product changes
                                        static::calculateTotal($get, $set);
                                    }
                                } else {
                                    $set('unit_price', 0);
                                    $set('expense_name', '');
                                    static::calculateTotal($get, $set);
                                }
                            }),

                        Forms\Components\TextInput::make('expense_name')
                            ->label('Expense Name')
                            ->required()
                            ->disabled(fn (callable $get) => $get('type') === 'Raw Material')
                            ->maxLength(255)
                            ->placeholder('Enter expense description'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Additional details about this expense (optional)'),
                    ])
                    ->columns(2),

                Section::make('Cost Calculation')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->default(1)
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required()
                            ->prefix('₱')
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->disabled()
                            ->numeric()
                            ->prefix('₱')
                            ->required()
                            ->dehydrated()
                            ->extraInputAttributes(['class' => 'font-bold']),

                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Expense Date')
                            ->default(now())
                            ->required()
                            ->maxDate(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_name')
                    ->label('Expense')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material' => 'success',
                        'Utility' => 'info',
                        'Labor' => 'warning',
                        'Equipment' => 'danger',
                        'Marketing' => 'primary',
                        'Transportation' => 'secondary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('php')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('php')
                    ->sortable()
                    ->summarize(Sum::make()->money('php')->label('Total Expenses'))
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M d, Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('expense_date_range')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '<=', $date),
                            );
                    }),

                Filter::make('month_year')
                    ->label('Month & Year')
                    ->form([
                        Select::make('month')
                            ->label('Month')
                            ->options([
                                '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                            ])
                            ->placeholder('Select month'),

                        Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                for ($i = 2; $i >= 0; $i--) {
                                    $year = now()->subYears($i)->year;
                                    $years[$year] = $year;
                                }
                                return $years;
                            })
                            ->placeholder('Select year'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['month']) || empty($data['year'])) {
                            return $query;
                        }

                        $start = Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
                        $end = Carbon::createFromDate($data['year'], $data['month'], 1)->endOfMonth();

                        return $query->whereBetween('expense_date', [$start, $end]);
                    }),

                Filter::make('type')
                    ->label('Category')
                    ->form([
                        Select::make('type')
                            ->label('Category')
                            ->options([
                                'Raw Material' => 'Raw Material',
                                'Utility' => 'Utility',
                                'Labor' => 'Labor',
                                'Equipment' => 'Equipment',
                                'Marketing' => 'Marketing',
                                'Transportation' => 'Transportation',
                                'Other' => 'Other',
                            ])
                            ->placeholder('Select category'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['type'],
                            fn (Builder $query, $type): Builder => $query->where('type', $type)
                        );
                    }),

                Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->prefix('₱'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->prefix('₱'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('expense_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    protected static function calculateTotal(callable $get, callable $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $totalAmount = $quantity * $unitPrice;
        $set('total_amount', round($totalAmount, 2));
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure total_amount is calculated before saving
        $quantity = (float) ($data['quantity'] ?? 0);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $data['total_amount'] = round($quantity * $unitPrice, 2);
        
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure total_amount is calculated before updating
        $quantity = (float) ($data['quantity'] ?? 0);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $data['total_amount'] = round($quantity * $unitPrice, 2);
        
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseLists::route('/'),
            'create' => Pages\CreateExpenseList::route('/create'),
            // 'view' => Pages\ViewExpenseList::route('/{record}'),
            'edit' => Pages\EditExpenseList::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        return true;
    }

    public static function canDelete($record): bool
    {
        return true;
    }
}