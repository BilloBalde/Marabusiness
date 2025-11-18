<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var \App\Models\User| \Spatie\Permission\Traits\HasRoles $user */
        if (!Filament::auth()->user()->hasRole('admin')) {
            // Get user IDs who have the admin role
            $adminRole = Role::where('name', 'admin')->first();

            if ($adminRole) {
                $adminUserIds = $adminRole->users()->pluck('id')->toArray();
                $query->whereNotIn('id', $adminUserIds);
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->maxlength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email verified at')
                    ->default(now()),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(),
                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->preload()
                    ->relationship('roles', 'name')
                    ->options(function () {
                        $query = Role::query();

                        // Hide the "admin" role unless the logged-in user is an admin
                        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
                        if (!Auth::user()->hasRole('admin')) {
                            $query->where('name', '!=', 'admin');
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->searchable(),
                /* Select::make('role')
                    ->label('Role')
                    ->options(Role::query()
                    ->when(auth()->user()?->hasRole('admin') === false, fn ($q) => $q->where('name', '!=', 'admin'))
                    ->pluck('name', 'name'))
                    ->default(fn (?User $record) => $record?->roles()->pluck('name')->first())
                    ->dehydrated(false) // prevents trying to save it to users table
                    ->required()
                    ->searchable(), // helpful for debug/testing */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\OrdersRelationManager::class,
            RelationManagers\ProjectsRelationManager::class
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
}
