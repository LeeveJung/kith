<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * User menu entries for switching the UI language. One entry per available
 * locale; the currently active language is hidden.
 */
class LanguageMenu
{
    /**
     * @return list<Action>
     */
    public static function items(): array
    {
        $items = [];

        foreach (config('app.available_locales', []) as $code => $name) {
            $items[] = Action::make("locale-{$code}")
                ->label($name)
                ->icon(Heroicon::OutlinedLanguage)
                ->url(fn (): string => route('locale.switch', $code))
                ->visible(fn (): bool => app()->getLocale() !== $code);
        }

        return $items;
    }
}
