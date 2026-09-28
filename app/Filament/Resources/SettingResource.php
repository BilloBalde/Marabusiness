<?php

namespace App\Filament\Resources;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Contenu du site';

    protected static ?string $modelLabel = 'Réglage';

    protected static ?string $pluralModelLabel = 'Réglages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Clé')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Identifiant technique, ex. sourcing_intro — utilisé tel quel dans le code.'),
                Forms\Components\Textarea::make('value.fr')
                    ->label('Valeur (français)')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value.fr')
                    ->label('Valeur (français)')
                    ->limit(80),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SettingResource\Pages\ListSettings::route('/'),
            'create' => \App\Filament\Resources\SettingResource\Pages\CreateSetting::route('/create'),
            'edit' => \App\Filament\Resources\SettingResource\Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
