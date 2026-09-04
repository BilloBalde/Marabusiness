<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('General')
                    ->schema([
                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug'),
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Category')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('brand.name')
                            ->label('Brand')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Translations')
                    ->schema([
                        Infolists\Components\Tabs::make('translations')
                            ->tabs([
                                Infolists\Components\Tabs\Tab::make('EN')
                                    ->schema($this->translationEntries('en')),
                                Infolists\Components\Tabs\Tab::make('FR')
                                    ->schema($this->translationEntries('fr')),
                                Infolists\Components\Tabs\Tab::make('ZH')
                                    ->schema($this->translationEntries('zh')),
                            ]),
                    ]),

                Infolists\Components\Section::make('Media')
                    ->schema([
                        Infolists\Components\ImageEntry::make('images')
                            ->label('Images')
                            ->disk('public_uploads')
                            ->visibility('public'),
                        Infolists\Components\ImageEntry::make('description_images')
                            ->label('Description Images')
                            ->disk('public_uploads')
                            ->visibility('public'),
                        Infolists\Components\TextEntry::make('video')
                            ->label('Uploaded Video')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('video_url')
                            ->label('Video URL')
                            ->placeholder('-'),
                        Infolists\Components\ImageEntry::make('video_thumbnail')
                            ->label('Video Thumbnail')
                            ->disk('public_uploads')
                            ->visibility('public'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_featured')
                            ->label('Featured')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('in_stock')
                            ->label('In Stock')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('on_sale')
                            ->label('On Sale')
                            ->boolean(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Shipping & Dimensions')
                    ->schema([
                        Infolists\Components\TextEntry::make('weight_formatted')
                            ->label('Weight'),
                        Infolists\Components\TextEntry::make('dimensions_formatted')
                            ->label('Dimensions'),
                        Infolists\Components\TextEntry::make('cbm_formatted')
                            ->label('CBM'),
                    ])
                    ->columns(3),
            ]);
    }

    private function translationEntries(string $locale): array
    {
        return [
            Infolists\Components\TextEntry::make("name_{$locale}")
                ->label('Name')
                ->placeholder('-')
                ->getStateUsing(fn () => $this->record->getTranslation('name', $locale)),

            Infolists\Components\TextEntry::make("short_description_{$locale}")
                ->label('Short Description')
                ->placeholder('-')
                ->getStateUsing(fn () => $this->record->getTranslation('short_description', $locale))
                ->prose(),

            Infolists\Components\TextEntry::make("description_{$locale}")
                ->label('Description')
                ->placeholder('-')
                ->getStateUsing(fn () => $this->record->getTranslation('description', $locale))
                ->prose(),
        ];
    }
}
