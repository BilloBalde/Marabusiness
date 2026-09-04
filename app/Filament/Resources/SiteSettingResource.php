<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?string $navigationLabel = 'Site Settings';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn($record) => $record !== null)
                            ->helperText('Unique key for this setting (cannot be changed after creation)'),
                            
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Human-readable label for this setting'),
                            
                        Forms\Components\Select::make('group')
                            ->options([
                                'general' => 'General',
                                'banners' => 'Banners',
                                'social' => 'Social Media',
                                'contact' => 'Contact Information',
                                'seo' => 'SEO',
                                'appearance' => 'Appearance',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Text',
                                'textarea' => 'Text Area',
                                'image' => 'Image',
                                'rich_editor' => 'Rich Editor',
                                'boolean' => 'Yes/No',
                                'number' => 'Number',
                                'email' => 'Email',
                                'url' => 'URL',
                                'json' => 'JSON',
                                'color' => 'Color',
                            ])
                            ->required()
                            ->reactive(),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->helperText('Description of what this setting does'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Setting Value')
                    ->schema(function (callable $get) {
                        $type = $get('type');
                        
                        switch ($type) {
                            case 'textarea':
                                return [
                                    Forms\Components\Textarea::make('value')
                                        ->rows(4)
                                        ->helperText('Enter text value')
                                ];
                                
                            case 'rich_editor':
                                return [
                                    Forms\Components\RichEditor::make('value')
                                        ->toolbarButtons([
                                            'bold', 'italic', 'underline',
                                            'strike', 'link', 'bulletList',
                                            'orderedList',
                                        ])
                                        ->helperText('Enter rich text content')
                                ];
                                
                            case 'image':
                                return [
                                    Forms\Components\FileUpload::make('value')
                                        ->label('Image')
                                        ->disk('public_uploads')
                                        ->directory('settings')
                                        ->helperText('Upload an image for this setting')
                                ];
                                
                            case 'boolean':
                                return [
                                    Forms\Components\Toggle::make('value')
                                        ->label('Enabled')
                                        ->inline(false)
                                        ->helperText('Enable or disable this setting')
                                        ->formatStateUsing(fn($state) => $state == '1' || $state === true || $state === 'true')
                                        ->dehydrateStateUsing(fn($state) => $state ? '1' : '0')
                                ];
                                
                            case 'number':
                                return [
                                    Forms\Components\TextInput::make('value')
                                        ->numeric()
                                        ->helperText('Enter a numeric value')
                                ];
                                
                            case 'email':
                                return [
                                    Forms\Components\TextInput::make('value')
                                        ->email()
                                        ->helperText('Enter an email address')
                                ];
                                
                            case 'url':
                                return [
                                    Forms\Components\TextInput::make('value')
                                        ->url()
                                        ->helperText('Enter a URL')
                                ];
                                
                            case 'json':
                                return [
                                    Forms\Components\KeyValue::make('value')
                                        ->keyLabel('Key')
                                        ->valueLabel('Value')
                                        ->helperText('Enter key-value pairs')
                                ];
                                
                            case 'color':
                                return [
                                    Forms\Components\ColorPicker::make('value')
                                        ->helperText('Select a color')
                                ];
                                
                            default: // text
                                return [
                                    Forms\Components\TextInput::make('value')
                                        ->helperText('Enter text value')
                                ];
                        }
                    }),
                    
                Forms\Components\Section::make('Advanced Options')
                    ->schema([
                        Forms\Components\KeyValue::make('options')
                            ->keyLabel('Option Key')
                            ->valueLabel('Option Value')
                            ->helperText('Additional options for this setting (e.g., for select fields)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('group')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'banners',
                        'warning' => 'social',
                        'danger' => 'contact',
                        'info' => 'seo',
                        'gray' => 'appearance',
                    ])
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'text',
                        'success' => 'image',
                        'warning' => 'boolean',
                        'gray' => 'textarea',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) {
                            return $state ?? 'Not set';
                        }
                        
                        if ($record->type === 'image' && $state) {
                            return '📷 Image uploaded';
                        }
                        if ($record->type === 'boolean') {
                            return $state == '1' || $state === true || $state === 'true' ? '✅ Yes' : '❌ No';
                        }
                        if (is_string($state) && strlen($state) > 50) {
                            return substr($state, 0, 50) . '...';
                        }
                        return $state ?? 'Not set';
                    })
                    ->wrap(),
                    
                Tables\Columns\ImageColumn::make('value')
                    ->label('Preview')
                    ->visible(fn($record): bool => 
                        $record && $record->type === 'image' && $record->value
                    )
                    ->disk('public_uploads')
                    ->width(50)
                    ->height(50),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group')
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Group')
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'banners' => 'Banners',
                        'social' => 'Social Media',
                        'contact' => 'Contact Information',
                        'seo' => 'SEO',
                        'appearance' => 'Appearance',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'textarea' => 'Text Area',
                        'image' => 'Image',
                        'boolean' => 'Yes/No',
                        'number' => 'Number',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        Cache::forget('site_settings');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('site_settings');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('site_settings');
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}