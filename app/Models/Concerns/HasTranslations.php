<?php

namespace App\Models\Concerns;

trait HasTranslations
{
    public function translate(string $field, ?string $locale = null): ?string
    {
        return $this->getTranslation($field, $locale);
    }

    protected function translationFallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }

    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale   = $locale ?: app()->getLocale();
        $fallback = $this->translationFallbackLocale();

        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        // 1) exact locale
        $value = optional($translations->firstWhere('locale', $locale))->{$field} ?? null;
        if (!empty($value)) return $value;

        // 2) fallback locale
        $value = optional($translations->firstWhere('locale', $fallback))->{$field} ?? null;
        if (!empty($value)) return $value;

        // 3) fallback to base column (raw)
        return $this->getRawOriginal($field) ?? null;
    }

    public function syncTranslations(array $byLocale): void
    {
        foreach ($byLocale as $locale => $fields) {
            $fields = array_filter($fields, fn ($v) => $v !== null);

            $this->translations()->updateOrCreate(
                ['locale' => $locale],
                $fields
            );
        }
    }
}
