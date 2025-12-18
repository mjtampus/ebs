<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductBatch;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\ProductBatchResource\Pages;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationGroup = 'Bakery';

    protected static ?string $navigationLabel = 'Batches';

    protected static ?string $modelLabel = 'Batch';

    protected static ?string $pluralModelLabel = 'Batches';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch Information')
                    ->description('Track your bread batches')
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Bread Product')
                            ->relationship('products', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('batch_number')
                            ->label('Batch Name')
                            ->placeholder('e.g., BATCH-001')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('batch_code')
                            ->label('Batch Code')
                            ->placeholder('e.g., 20250101-WW')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('products.name')
                    ->label('Bread Product')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('batch_code')
                    ->label('Batch Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('products', 'name')
                    ->label('Bread Product'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->label('mark as expired')
                ->icon('heroicon-o-exclamation-triangle'),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkExpire')
                        ->label('Mark as Expired')
                        ->icon('heroicon-o-clock')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Batches as Expired')
                        ->modalDescription('Are you sure you want to mark the selected batches as expired?')
                        ->modalSubmitActionLabel('Yes, Mark as Expired')
                        ->action(function (Collection $records) {

                            \Log::info('hellow');

                            Notification::make()
                                ->title('Batches marked as expired')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductBatches::route('/'),
            'create' => Pages\CreateProductBatch::route('/create'),
            // 'view' => Pages\ViewProductBatch::route('/{record}'),
            'edit' => Pages\EditProductBatch::route('/{record}/edit'),
        ];
    }

    public static function canAccess() :bool
    {
        return Auth::user()->role === 'cashier';
    }
}
