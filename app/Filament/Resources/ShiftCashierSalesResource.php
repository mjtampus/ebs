<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftCashierSalesResource\Pages;
use App\Models\Transaction;
use App\Models\OpeningFloat;
use App\Models\CashDrop;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;
class ShiftCashierSalesResource extends Resource
{
    protected static ?string $model = OpeningFloat::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Shift Cashier Sales';
    protected static ?string $navigationGroup = 'POS Transactions';
    protected static ?string $modelLabel = 'Shift Record';
    protected static ?string $pluralModelLabel = 'Shift Records';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user && $user->role === 'admin';
    }
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(self::getShiftBasedQuery())
            ->columns([
                TextColumn::make('cashier_name')
                    ->label('Cashier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shift_start')
                    ->label('Shift Start')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('shift_end')
                    ->label('Shift End (24hrs)')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('opening_float')
                    ->label('Opening Float')
                    ->money('PHP')
                    ->sortable(),

                // TextColumn::make('transaction_count')
                //     ->label('# Trans')
                //     ->badge()
                //     ->color('info')
                //     ->alignCenter(),

                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->money('PHP')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('PHP'),
                    ]),



                TextColumn::make('expected_cash')
                    ->label('Total Cash Amount')
                    ->money('PHP')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('cash_drop')
                    ->label('Cash Drop')
                    ->money('PHP')
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('remaining_cash')
                    ->label('Cash in Register')
                    ->money('PHP')
                    ->color(fn($record) => $record->remaining_cash < 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('cashier')
                    ->label('Cashier')
                    ->options(
                        User::whereHas('openingFloats')->pluck('name', 'id')
                    )
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            return $query->where('opening_floats.user_id', $data['value']);
                        }
                    }),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q, $date) => $q->whereDate('opening_floats.created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn($q, $date) => $q->whereDate('opening_floats.created_at', '<=', $date)
                            );
                    }),
            ])

            ->defaultSort('shift_start', 'desc')
            ->poll('30s');
    }

    protected static function getShiftBasedQuery()
    {
        return OpeningFloat::query()
            ->select([
                'opening_floats.id',
                'opening_floats.user_id',
                'opening_floats.amount as opening_float',
                'opening_floats.created_at as shift_start',
                DB::raw('DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR) as shift_end'),
            ])
            ->selectRaw('(SELECT name FROM users WHERE users.id = opening_floats.user_id) as cashier_name')
            ->selectRaw('
            COALESCE((
                SELECT COUNT(*)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) as transaction_count
        ')
            ->selectRaw('
            COALESCE((
                SELECT SUM(total_amount)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) as total_sales
        ')
            ->selectRaw('
            COALESCE((
                SELECT SUM(amount_received)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) as cash_received
        ')
            ->selectRaw('
            COALESCE((
                SELECT SUM(`change`)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) as change_given
        ')
            ->selectRaw('
            COALESCE((
                SELECT SUM(amount)
                FROM cash_drops
                WHERE cash_drops.opening_float_id = opening_floats.id
            ), 0) as cash_drop
        ')
            ->selectRaw('
            opening_floats.amount +
            COALESCE((
                SELECT SUM(amount_received)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) -
            COALESCE((
                SELECT SUM(`change`)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) as expected_cash
        ')
            ->selectRaw('
            opening_floats.amount +
            COALESCE((
                SELECT SUM(amount_received)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) -
            COALESCE((
                SELECT SUM(`change`)
                FROM transactions
                WHERE transactions.cashier_id = opening_floats.user_id
                AND transactions.created_at >= opening_floats.created_at
                AND transactions.created_at < DATE_ADD(opening_floats.created_at, INTERVAL 24 HOUR)
                AND transactions.deleted_at IS NULL
            ), 0) -
            COALESCE((
                SELECT SUM(amount)
                FROM cash_drops
                WHERE cash_drops.opening_float_id = opening_floats.id
            ), 0) as remaining_cash
        ');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShiftManagement::route('/'),
        ];
    }
}
