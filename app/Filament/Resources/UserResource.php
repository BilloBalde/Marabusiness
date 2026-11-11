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
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.users');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Get the authenticated user using Filament's auth
        $authUser = Filament::auth()->user();
        
        if (!$authUser->hasRole('admin')) {
            // Get user IDs who have the admin role
            $adminRole = Role::where('name', 'admin')->first();
            
            if ($adminRole) {
                $adminUserIds = $adminRole->users()->pluck('id')->toArray();
                $query->whereNotIn('id', $adminUserIds);
            }
            
            // If user is manager, only show vendors and customers
            if ($authUser->hasRole('manager')) {
                $vendorRole = Role::where('name', 'vendor')->first();
                $customerRole = Role::where('name', 'customer')->first();
                
                $vendorUserIds = $vendorRole ? $vendorRole->users()->pluck('id')->toArray() : [];
                $customerUserIds = $customerRole ? $customerRole->users()->pluck('id')->toArray() : [];
                
                $allowedUserIds = array_merge($vendorUserIds, $customerUserIds);
                $query->whereIn('id', $allowedUserIds);
            }
        }
        
        return $query;
    }

    /**
     * The roles the signed-in staff member may give to an account, as [name => name]:
     * an admin any of them, a manager only vendor and customer. Anyone else — who cannot
     * reach this resource anyway — gets none.
     *
     * Used both to fill the role field and to refuse a role that was not on offer, so the
     * two can never disagree.
     *
     * @return array<string, string>
     */
    public static function assignableRoles(): array
    {
        $user = Filament::auth()->user();

        if ($user?->hasRole('admin')) {
            return Role::query()->pluck('name', 'name')->all();
        }

        if ($user?->hasRole('manager')) {
            return Role::query()->whereIn('name', ['vendor', 'customer'])->pluck('name', 'name')->all();
        }

        return [];
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
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->required(fn(string $context): bool => $context === 'create')
                    ->confirmed(),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->dehydrated(false)
                    ->required(fn (string $context): bool => $context === 'create'),

                
                Select::make('role')
                    ->label('Role')
                    ->options(fn () => static::assignableRoles())
                    // A Select does not turn its options into a validation rule, and
                    // EditUser/CreateUser read the role back from the raw form state: without
                    // this a manager could submit "admin" for any account (and set its
                    // password in the same form).
                    ->in(fn () => array_keys(static::assignableRoles()))
                    ->default(fn (?User $record) => $record?->roles()->pluck('name')->first())
                    ->required()
                    ->searchable(),
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
                Tables\Actions\EditAction::make()
                    ->hidden(fn(User $record): bool => 
                        // Hide edit action for admin users if current user is not admin
                        !Filament::auth()->user()->hasRole('admin') && 
                        $record->hasRole('admin')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(User $record): bool => 
                        // Hide delete action for admin users if current user is not admin
                        !Filament::auth()->user()->hasRole('admin') && 
                        $record->hasRole('admin')
                    ),
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