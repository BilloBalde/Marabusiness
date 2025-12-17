<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.extras');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.contacts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->disabled()
                            ->dehydrated(false)
                            ->nullable(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Message Details')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(10)
                            ->columnSpanFull(),
                            
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Received At')
                            ->disabled()
                            ->dehydrated(false)
                            ->displayFormat('F j, Y g:i A'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListContacts::route('/'),
            // Removed create and edit routes since we only want viewing
        ];
    }
}