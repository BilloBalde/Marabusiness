<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Four mobile-API routes were declared after a parameterized route that already
 * matched their exact URL — Laravel dispatches to whichever route was registered
 * first, not whichever is more specific, so each of these was silently swallowed
 * by a "show one record by id" route reading the literal word ("featured", "tree",
 * "reviews") as if it were an id or a slug:
 *
 *   GET /brands/featured        -> was BrandController::show(id: "featured")
 *   GET /categories/tree        -> was CategoryController::show(id: "tree")
 *   GET /categories/featured    -> was CategoryController::show(id: "featured")
 *   GET /products/reviews/{id}  -> was ProductController::show(slug: "reviews", ...)
 *
 * Fixed by moving the literal-segment routes before the parameterized ones in
 * routes/api.php. show($id) 404s when nothing matches that id (correctly so:
 * findOrFail() on the literal string "featured"/"tree" never finds a row), so a
 * plain assertOk() already tells the two apart; asserting the exact success
 * message pins down *which* controller method actually answered, not just that
 * something did.
 */
class ApiRouteOrderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function brands_featured_is_not_read_as_a_brand_id(): void
    {
        $response = $this->getJson('/api/v1/brands/featured');

        $response->assertOk();
        $response->assertJsonPath('message', 'Featured brands retrieved successfully');
    }

    #[Test]
    public function categories_tree_is_not_read_as_a_category_id(): void
    {
        // No categories need to exist — an empty tree is still a valid 200, and
        // that alone is enough to prove tree() answered rather than show('tree').
        $response = $this->getJson('/api/v1/categories/tree');

        $response->assertOk();
        $response->assertJsonPath('message', 'Category tree retrieved successfully');
    }

    #[Test]
    public function categories_featured_is_not_read_as_a_category_id(): void
    {
        $response = $this->getJson('/api/v1/categories/featured');

        $response->assertOk();
        $response->assertJsonPath('message', 'Featured categories retrieved successfully');
    }

    #[Test]
    public function a_products_slug_route_still_matches_a_real_two_segment_slug(): void
    {
        // The fix moves the literal /reviews and /review prefixes ahead of
        // {slug}/{vendor_product_id} — confirm that generic route still catches
        // an ordinary product URL and isn't itself now shadowed by the reorder.
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/api/v1/products/some-real-slug/42', 'GET')
        );

        $this->assertSame(
            \App\Http\Controllers\Api\ProductController::class . '@show',
            $route->getActionName()
        );
    }
}
