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
                Forms\Components\Section::make('Personal Information')
                    ->description('Enter the user\'s basic information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('John Doe')
                                    ->prefixIcon('heroicon-o-user'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('john@example.com')
                                    ->prefixIcon('heroicon-o-envelope'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('contact')
                                    ->label('Contact Number')
                                    ->tel()
                                    ->maxLength(15)
                                    ->required()
                                    ->placeholder('+63 912 345 6789')
                                    ->prefixIcon('heroicon-o-phone'),

                                Forms\Components\Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-o-user-circle')
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpan('full'),

                Forms\Components\Section::make('Account Settings')
                    ->description('Configure user account credentials and role')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->required()
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->autocomplete('new-password')
                                    ->revealable()
                                    ->prefixIcon('heroicon-o-lock-closed')
                                    ->helperText('Must be at least 8 characters long'),

                                Forms\Components\Select::make('role')
                                    ->label('User Role')
                                    ->required()
                                    ->options([
                                        'admin' => 'Admin',
                                        'cashier' => 'Cashier',
                                    ])
                                    ->reactive()
                                    ->prefixIcon('heroicon-o-shield-check')
                                    ->native(false)
                                    ->helperText('Select the appropriate role for this user'),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpan('full'),

                Forms\Components\Section::make('Shift Schedule')
                    ->description('Configure working hours and shift timing')
                    ->schema([
                        Forms\Components\Select::make('shift')
                            ->label('Shift Type')
                            ->options([
                                'day' => 'Day Shift (9:00 AM - 5:00 PM)',
                                'night' => 'Night Shift (10:00 PM - 6:00 AM)',
                                'custom' => 'Custom Schedule',
                            ])
                            ->required()
                            ->visible(fn (callable $get) => $get('role') === 'staff' || $get('role') === 'cashier')
                            ->reactive()
                            ->native(false)
                            ->prefixIcon('heroicon-o-clock')
                            ->afterStateUpdated(function ($state, callable $set) {
                                \Log::info('Shift updated to: ' . $state);

                                if ($state === 'custom') {
                                    $set('shift_start', null);
                                    $set('shift_end', null);
                                } elseif ($state === 'day') {
                                    $set('shift_start', '09:00');
                                    $set('shift_end', '17:00');
                                } elseif ($state === 'night') {
                                    $set('shift_start', '22:00');
                                    $set('shift_end', '06:00');
                                }
                            })
                            ->columnSpan('full'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('shift_start')
                                    ->label('Shift Start Time')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('role') === 'cashier' || $get('role') === 'staff')
                                    ->disabled(fn (callable $get) => $get('shift') !== 'custom')
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-arrow-right-circle')
                                    ->seconds(false)
                                    ->afterStateUpdated(function ($state) {
                                        \Log::info('Shift start field updated to: ' . $state);
                                    }),

                                Forms\Components\TimePicker::make('shift_end')
                                    ->label('Shift End Time')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('role') === 'cashier' || $get('role') === 'staff')
                                    ->disabled(fn (callable $get) => $get('shift') !== 'custom')
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-arrow-left-circle')
                                    ->seconds(false)
                                    ->afterStateUpdated(function ($state) {
                                        \Log::info('Shift end field updated to: ' . $state);
                                    }),
                            ])
                            ->visible(fn (callable $get) => $get('role') === 'cashier' || $get('role') === 'staff'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (callable $get) => $get('role') === 'cashier' || $get('role') === 'staff')
                    ->columnSpan('full'),
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
                        'staff' => 'Staff',
                    ]),
                // Add these columns to see if data is actually in the database
                Tables\Columns\TextColumn::make('shift')->label('Shift'),
                Tables\Columns\TextColumn::make('shift_start')->label('Start Time'),
                Tables\Columns\TextColumn::make('shift_end')->label('End Time'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'cashier' => 'Cashier',
                        'staff' => 'Staff',
                    ]),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
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
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
