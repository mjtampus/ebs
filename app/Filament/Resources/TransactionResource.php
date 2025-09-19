<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationLabel = "Sale Records";
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    // protected static ?string $navigationLabel = 'Transactions';
    protected static ?string $navigationGroup = 'POS Transactions';
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('Transaction Code')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('cashier.name')
                    ->label('Cashier')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->getStateUsing(function ($record) {
                        $items = json_decode($record->items, true);
                        return is_array($items) ? count($items) : 0;
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->formatStateUsing(fn($state) => 'PHP ' . number_format($state, 2))
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Transaction Information')
                    ->schema([
                        TextEntry::make('transaction_code')
                            ->label('Transaction Code'),

                        TextEntry::make('cashier.name')
                            ->label('Cashier'),

                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('M d, Y h:i:s A'),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->formatStateUsing(fn($state) => 'PHP ' . number_format($state, 2)),

                        TextEntry::make('amount_received')
                            ->label('Amount Received')
                            ->formatStateUsing(fn($state) => 'PHP ' . number_format($state, 2)),

                        TextEntry::make('change')
                            ->label('Change')
                            ->formatStateUsing(fn($state) => 'PHP ' . number_format($state, 2)),
                    ])
                    ->columns(3),

                Section::make('Items Purchased')
                    ->schema([
                        RepeatableEntry::make('parsed_items')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $items = json_decode($record->items, true);
                                if (!is_array($items))
                                    return [];

                                return collect($items)->map(function ($item) {
                                    $product = Product::find($item['product_id']);
                                    return [
                                        'product_name' => $product ? $product->name : 'Product Not Found',
                                        'quantity' => $item['quantity'],
                                        'unit_price' => $item['unit_price'],
                                        'total_price' => $item['total_price'] ?? ($item['quantity'] * $item['unit_price']),
                                    ];
                                })->toArray();
                            })
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Product'),

                                TextEntry::make('quantity')
                                    ->label('Qty')
                                    ->badge(),

                                TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->formatStateUsing(fn($state) => 'PHP ' . number_format($state, 2)),

                                TextEntry::make('total_price')
                                    ->label('Total')
                                    ->formatStateUsing(fn($state) => 'PHP ' . number_format($state, 2)),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
