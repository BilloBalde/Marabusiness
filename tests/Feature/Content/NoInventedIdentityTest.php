<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The public pages used to carry an invented company: a registration number, a share
 * capital, addresses in Dakar and Paris, a "publication director", a lawyer, a DPO and
 * an EU representative with names and mailboxes, a founding story, statistics and a
 * team photographed from a stock library. A legal notice with made-up identifiers is
 * worse than an incomplete one, so the details now come from config/company.php and the
 * site settings, and this test keeps the old inventions from creeping back into a view.
 */
class NoInventedIdentityTest extends TestCase
{
    use RefreshDatabase;

    private const INVENTED = [
        '@marabusiness.com', '@marabusiness.eu',
        '+221 78 123 45 67', '+221781234567', '+221 78 987 65 43',
        '+33 1 23 45 67 89', '+33 6 12 34 56 78', '+123 456 7890',
        'SN-DKR-2020-B-12345', 'JINEIYATECH HOSTING', 'DSArep',
        'Amadou Diallo', 'Fatou Ndiaye', 'Moussa Keita', 'Aminata Sow',
        '123 Rue Principale', '123 Main Street', 'yourcompany.com',
        'Trustpilot', '2,500+', 'Fondée en 2020',
    ];

    #[Test]
    public function no_view_contains_an_invented_identity_or_contact(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                continue; // published Filament views, not ours
            }

            $content = File::get($file->getPathname());

            // Blade comments explaining what used to be there are allowed to name it.
            $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);

            foreach (self::INVENTED as $needle) {
                if (str_contains($content, $needle)) {
                    $offenders[] = $file->getRelativePathname() . ' contains "' . $needle . '"';
                }
            }
        }

        $this->assertSame([], $offenders, "Invented identity found:\n" . implode("\n", $offenders));
    }

    #[Test]
    public function the_public_information_pages_still_render_without_any_company_configuration(): void
    {
        config(['company' => array_fill_keys(array_keys(config('company')), null)]);

        foreach ([
            '/legal', '/terms-of-use', '/privacy-policy', '/cookies', '/security', '/product-safety',
            '/intellectual-property', '/digital-services', '/faq', '/delivery', '/returns',
            '/buyer-protection', '/about-us', '/contact',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    #[Test]
    public function the_legal_notice_shows_what_is_configured_and_only_that(): void
    {
        config([
            'company.name' => 'Société Exemple SARL',
            'company.registration' => 'GN-CKY-2024-B-0001',
            'company.email' => 'juridique@exemple.test',
            'company.publication_director' => 'Mariama Camara',
        ]);

        $this->get('/legal')
            ->assertOk()
            ->assertSee('Société Exemple SARL')
            ->assertSee('GN-CKY-2024-B-0001')
            ->assertSee('juridique@exemple.test')
            ->assertSee('Mariama Camara')
            ->assertDontSee('NIF');
    }
}
