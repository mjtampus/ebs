<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'User Management';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }


public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->required()
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->autocomplete('new-password'),

                    Forms\Components\Select::make('role')
                        ->label('User Role')
                        ->required()
                        ->options([
                            'admin' => 'Admin',
                            'cashier' => 'Cashier',
                        ])
                        ->reactive(), // ← Make it reactive so dependent fields update
                ]),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('contact')
                        ->label('Contact Number')
                        ->tel()
                        ->maxLength(15)
                        ->required(),

                    Forms\Components\Select::make('gender')
                        ->label('Gender')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                        ])
                        ->required(),
                ]),

            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Select::make('shift')
                        ->label('Shift')
                        ->options([
                            'day' => 'Day',
                            'night' => 'Night',
                        ])
                        ->required()
                        ->visible(fn (callable $get) => $get('role') === 'cashier'),

                    Forms\Components\TimePicker::make('shift_start')
                        ->label('Shift Start')
                        ->required()
                        ->default('08:00')
                        ->visible(fn (callable $get) => $get('role') === 'cashier'),

                    Forms\Components\TimePicker::make('shift_end')
                        ->label('Shift End')
                        ->required()
                        ->default('17:00')
                        ->visible(fn (callable $get) => $get('role') === 'cashier'),
                ]),
        ])
        ->columns(1);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('email_verified_at')->dateTime()->sortable(),
            Tables\Columns\SelectColumn::make('role')
                ->options([
                    'admin' => 'Admin',
                    'cashier' => 'Cashier',
                ]),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('role')
                ->options([
                    'admin' => 'Admin',
                    'cashier' => 'Cashier',
                ]),
        ])
        ->headerActions([
            // Tables\Actions\CreateAction::make(), // Enables modal-based "Create" action
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
