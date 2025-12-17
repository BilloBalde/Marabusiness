<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.extras');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.services');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->disabled()
                    ->dehydrated()
                    ->unique(Service::class, 'slug', ignoreRecord: true),

                Forms\Components\Section::make('Service Features')
                    ->description('Add key features of this service')
                    ->schema([
                        Forms\Components\TagsInput::make('features')
                            ->label('Features')
                            ->placeholder('Type a feature and press Enter')
                            ->splitKeys(['Tab', 'Enter'])
                            ->helperText('Add features that describe this service. Press Enter after each feature.')
                            ->columnSpanFull(),
                    ]),

                FileUpload::make('icon')
                    ->disk('public_uploads')
                    ->directory('services')
                    ->columnSpanFull(),

                MarkdownEditor::make('description')
                    ->columnSpanFull()
                    ->fileAttachmentsDirectory('services'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('features')
                    ->label('Features')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return 'No features';
                        
                        $features = is_string($state) ? json_decode($state, true) : $state;
                        if (is_array($features) && count($features) > 0) {
                            return implode(', ', array_slice($features, 0, 3)) . 
                                (count($features) > 3 ? '...' : '');
                        }
                        return 'No features';
                    })
                    ->tooltip(function ($state) {
                        if (empty($state)) return null;
                        
                        $features = is_string($state) ? json_decode($state, true) : $state;
                        if (is_array($features) && count($features) > 0) {
                            return implode(', ', $features);
                        }
                        return null;
                    })
                    ->color('gray')
                    ->wrap(),

                Tables\Columns\ImageColumn::make('image')
                    ->disk('public_uploads'),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                ])
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
