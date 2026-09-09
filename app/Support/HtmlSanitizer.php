<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans vendor-authored rich text before it reaches a browser.
 *
 * Product and service descriptions are written by vendors in a rich editor and were
 * rendered raw with {!! !!}, so a vendor could put a <script> tag in their own product
 * description and have it run in every buyer's browser that opened the page. Filament's
 * editor does no server-side filtering, and the value is stored as HTML, so escaping it
 * at render time would break every legitimate description instead.
 *
 * Applied at display rather than on save: the same text is written from the Filament
 * form, from EditProduct::afterSave() and into the per-locale product_translations rows,
 * so filtering on write would mean covering each of those paths without missing one, and
 * would still leave everything already stored untouched. One call at the render site
 * covers all of it, translations and existing rows included.
 */
class HtmlSanitizer
{
    private static ?SymfonyHtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return static::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): SymfonyHtmlSanitizer
    {
        return static::$sanitizer ??= new SymfonyHtmlSanitizer(
            (new HtmlSanitizerConfig())
                // Formatting, headings, lists, tables — everything a description needs,
                // minus scripts, iframes, event handlers and style injection.
                ->allowSafeElements()
                ->allowAttribute('src', ['img'])
                ->allowAttribute('alt', ['img'])
                ->allowAttribute('href', ['a'])
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->allowMediaSchemes(['https', 'http', 'data'])
                // Required: product images are stored as relative paths under
                // /uploads/..., and without these two the sanitizer drops the src of
                // every image already in the catalogue.
                ->allowRelativeLinks()
                ->allowRelativeMedias()
        );
    }
}
