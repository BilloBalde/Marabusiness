<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RealisationsRelationManager extends RelationManager
{
    protected static string $relationship = 'realisations';

    protected static ?string $recordTitleAttribute = 'evolution';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                ToggleButtons::make('evolution')
                    ->options([
                        '10%' => '10%',
                        '25%' => '25%',
                        '50%' => '50%',
                        '75%' => '75%',
                        '100%' => '100%'
                    ])
                    ->default('10%')
                    ->inline()
                    ->required()
                    ->colors([
                        '10%' => 'danger',
                        '25%' => 'danger',
                        '50%' => 'info',
                        '75%' => 'Warning',
                        '100%' => 'success'
                    ])
                    ->icons([
                        '10%' => 'heroicon-m-sparkles',
                        '25%' => 'heroicon-m-arrow-path',
                        '50%' => 'heroicon-m-truck',
                        '75%' => 'heroicon-m-check-badge',
                        '100%' => 'heroicon-m-x-circle'
                    ]),
                MarkdownEditor::make('description')
                    ->columnSpanFull()
                    ->fileAttachmentsDirectory('project_realisations'),
                FileUpload::make('images')
                    ->multiple()
                    ->disk('public_uploads')
                    ->directory('project_realisations')
                    ->maxFiles(5)
                    ->reorderable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('evolution'),
                Tables\Columns\TextColumn::make('description')->limit(30),
                Tables\Columns\ImageColumn::make('images')->circular()->limit(1),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
