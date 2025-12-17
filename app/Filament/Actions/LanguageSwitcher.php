<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;

class LanguageSwitcher
{
    public static function make(): Action
    {
        return Action::make('language')
            ->label(strtoupper(app()->getLocale()))
            ->color('gray')
            ->icon('heroicon-o-language')
            ->tooltip('Switch Language')
            ->modalHeading('Change Language')
            ->modalDescription('Select your preferred language.')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(view('filament.language-modal'));
    }
}
