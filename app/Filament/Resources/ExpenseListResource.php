<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
// use Filament\Forms\Set;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Ramsey\Collection\Set;
use App\Models\ExpenseList;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ExpenseListResource\Pages;
use App\Filament\Resources\ExpenseListResource\RelationManagers;

class ExpenseListResource extends Resource
{
    protected static ?string $model = ExpenseList::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Financial Tracking';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Expense Category')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Utility' => 'Utility',
                        'Other' => 'Other',
                    ])
                    ->required()
                    ->live(),

                Forms\Components\Select::make('product_id')
                    ->label('Raw Material')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) =>
                            $query->whereHas('product_category', fn ($q) =>
                                $q->where('has_unit', 1)
                            )
                    )
                    ->visible(fn (callable $get) => $get('type') === 'Raw Material')
                    ->required(fn (callable $get) => $get('type') === 'Raw Material')
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = Product::with('product_category')->find($state);
                            if ($product) {
                                $set('unit_price', $product->unit_price ?? 0);
                                $set('expense_name', $product->name);
                                $set('type', $product->product_category->type ?? 'Raw Material');
                            }
                        }
                    })
                    ->reactive(),

                Forms\Components\TextInput::make('expense_name')
                    ->required(fn (callable $get) => $get('type') !== 'Raw Material')
                    ->disabled(fn (callable $get) => $get('type') === 'Raw Material')
                    ->maxLength(255),

                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(0)
                    ->required()
                    ->live()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),

                Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->live()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),

                Forms\Components\TextInput::make('total_amount')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->summarize(Sum::make()->money('php'))
                    ->money('php')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Filter::make('created_month')
                    ->form([
                        Select::make('month')
                            ->label('Month')
                            ->options([
                                '01' => 'January',
                                '02' => 'February',
                                '03' => 'March',
                                '04' => 'April',
                                '05' => 'May',
                                '06' => 'June',
                                '07' => 'July',
                                '08' => 'August',
                                '09' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December',
                            ])
                            ->required()
                            ->placeholder('Select month'),
                        
                        Select::make('year')
                            ->label('Year')
                            ->options([
                                now()->subYears(2)->year => now()->subYears(2)->year,
                                now()->subYear()->year => now()->subYear()->year,
                                now()->year => now()->year,
                            ])
                            ->required()
                            ->placeholder('Select year'),
                    ])
                        ->query(function ($query, array $data) {
                            if (!filled($data['month'])) {
                                return $query;                            }

                            $year = filled($data['year']) ? $data['year'] : now()->year;
                            $month = $data['month'];

                            $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
                            $end = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();

                            return $query->whereBetween('created_at', [$start, $end]);
                        }),
                        Tables\Filters\TrashedFilter::make(),     
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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

    protected static function calculateTotal(callable $get, callable $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $totalAmount = $quantity * $unitPrice;
        $set('total_amount', number_format($totalAmount, 2, '.', ''));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseLists::route('/'),
            'create' => Pages\CreateExpenseList::route('/create'),
            'edit' => Pages\EditExpenseList::route('/{record}/edit'),
        ];
    }
}
