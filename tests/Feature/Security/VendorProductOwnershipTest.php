<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\VendorProductsRelationManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ProductResource scoped a vendor's "Produits" list, the Edit action's visibility,
 * and EditProduct's own access check all on products.created_by alone. Real
 * ownership in this marketplace is the vendor_product pivot — that's what the
 * storefront, checkout and the price column all read. created_by only records
 * who typed the catalog entry in, which can be an admin, or nobody at all.
 *
 * A real product ("tshitNike", id 22, created_by NULL) sold by OmarShop was
 * invisible in OmarShop's own product list and threw them straight back out if
 * they somehow reached its edit URL directly. Fixed by treating either signal as
 * ownership — created_by alone is kept too, so a vendor's own product is still
 * visible to them the moment they create it, before any vendor_product row exists.
 *
 * Fixing this also surfaced an unrelated gap in the same tab: VendorProductsRelation
 * Manager let anyone — including a vendor — assign a listing to ANY vendor via a
 * free vendor_id select, and its bulk actions were not scoped by panel at all.
 */
class VendorProductOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function vendorUser(): array
    {
        $vendor = Vendor::factory()->create();
        $user = $vendor->user;

        return [$vendor, $user];
    }

    private function actingAsVendor(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        $this->actingAs($user);
    }

    /**
     * Neither Product, Category, Brand nor VendorProduct have a factory in this
     * project — built directly rather than growing factory infrastructure for a
     * single test file.
     */
    private function product(?int $createdBy): Product
    {
        // Fresh rows every call rather than cached statics: RefreshDatabase wipes
        // the database between tests, but a static local inside this method would
        // survive across test methods within the same PHP process and hand back
        // an id that no longer exists.
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category-' . uniqid()]);
        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand-' . uniqid()]);

        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Test Product ' . uniqid(),
            'slug' => 'test-product-' . uniqid(),
            'created_by' => $createdBy,
        ]);
    }

    private function listing(int $vendorId, int $productId): VendorProduct
    {
        return VendorProduct::create([
            'vendor_id' => $vendorId,
            'product_id' => $productId,
            'price' => 100,
            'stock' => 10,
        ]);
    }

    #[Test]
    public function a_vendor_sees_a_product_they_list_even_if_someone_else_or_nobody_created_it(): void
    {
        [$vendor, $user] = $this->vendorUser();

        $product = $this->product(null);
        $this->listing($vendor->id, $product->id);

        $this->actingAsVendor($user);

        $this->assertTrue(
            ProductResource::getEloquentQuery()->whereKey($product->id)->exists()
        );
    }

    #[Test]
    public function a_vendor_does_not_see_a_product_they_neither_created_nor_list(): void
    {
        [, $user] = $this->vendorUser();
        $otherVendor = Vendor::factory()->create();

        $product = $this->product(null);
        $this->listing($otherVendor->id, $product->id);

        $this->actingAsVendor($user);

        $this->assertFalse(
            ProductResource::getEloquentQuery()->whereKey($product->id)->exists()
        );
    }

    #[Test]
    public function a_freshly_created_product_with_no_listing_yet_is_still_visible_to_its_creator(): void
    {
        // Before any vendor_product row exists — the moment right after creation.
        [, $user] = $this->vendorUser();
        $product = $this->product($user->id);

        $this->actingAsVendor($user);

        $this->assertTrue(
            ProductResource::getEloquentQuery()->whereKey($product->id)->exists()
        );
    }

    #[Test]
    public function a_vendor_can_open_the_edit_page_of_a_product_they_only_list(): void
    {
        [$vendor, $user] = $this->vendorUser();
        $product = $this->product(null);
        $this->listing($vendor->id, $product->id);

        $this->actingAsVendor($user);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful();
    }

    #[Test]
    public function a_vendor_cannot_reach_the_edit_page_of_a_product_that_is_neither_theirs_nor_listed_by_them(): void
    {
        [, $user] = $this->vendorUser();
        $otherVendor = Vendor::factory()->create();
        $product = $this->product(null);
        $this->listing($otherVendor->id, $product->id);

        $this->actingAsVendor($user);

        // Filament resolves {record} for the edit page through the resource's own
        // getEloquentQuery() (Resource::resolveRecordRouteBinding()) — since that
        // query already excludes a product this vendor neither created nor lists,
        // the record itself never resolves. EditProduct::authorizeAccess()'s own
        // created_by/vendor_product check is a second layer that would only matter
        // if route binding weren't already scoped; here it is, so the failure
        // surfaces earlier, as a 404 rather than a redirect.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);
    }

    // --- VendorProductsRelationManager cross-vendor gap -----------------------

    #[Test]
    public function a_vendor_cannot_see_another_vendors_offer_on_a_shared_product(): void
    {
        [$vendor, $user] = $this->vendorUser();
        $otherVendor = Vendor::factory()->create();
        $product = $this->product($user->id);

        $this->listing($vendor->id, $product->id);
        $otherOffer = $this->listing($otherVendor->id, $product->id);

        $this->actingAsVendor($user);

        Livewire::test(VendorProductsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->assertCanNotSeeTableRecords([$otherOffer]);
    }

    #[Test]
    public function the_vendor_id_field_is_locked_to_the_current_vendor_in_the_vendor_panel(): void
    {
        [$vendor, $user] = $this->vendorUser();
        $product = $this->product($user->id);

        $this->actingAsVendor($user);

        Livewire::test(VendorProductsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableAction('create', data: [
                'price' => 100,
                'stock' => 5,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('vendor_product', [
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
        ]);
    }
}
