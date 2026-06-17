<?php

namespace Tests\Feature;

use App\Models\CatalogueFeature;
use App\Models\Package;
use App\Models\Product;
use App\Models\SupportTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function finance(): User
    {
        return User::where('email', 'finance@codescaletech.test')->firstOrFail();
    }

    public function test_catalogue_seeder_creates_scalelens_product_and_packages(): void
    {
        $this->seed();

        $product = Product::where('code', 'SCALELENS')->firstOrFail();

        $this->assertSame('ScaleLens', $product->name);
        $this->assertSame(3, $product->packages()->count());
        $this->assertDatabaseHas('packages', [
            'code' => 'SCALELENS-GROWTH',
            'product_id' => $product->id,
            'billing_interval' => 'once_off',
            'price_min' => '300000.00',
            'price_max' => '600000.00',
        ]);

        // Enterprise is an open-ended "from" tier — no upper bound.
        $this->assertDatabaseHas('packages', ['code' => 'SCALELENS-ENTERPRISE', 'price_min' => '600000.00', 'price_max' => null]);
    }

    public function test_catalogue_seeder_creates_scalelens_features_and_support_tiers(): void
    {
        $this->seed();

        $product = Product::where('code', 'SCALELENS')->firstOrFail();
        $enterprise = Package::where('code', 'SCALELENS-ENTERPRISE')->firstOrFail();

        $this->assertSame(15, CatalogueFeature::where('product_id', $product->id)->count());
        $this->assertDatabaseHas('catalogue_features', [
            'code' => 'SCALELENS-FEATURE-DOCUMENT-REGISTER',
            'product_id' => $product->id,
            'minimum_package_id' => $enterprise->id,
        ]);

        $this->assertSame(3, SupportTier::count());
        $this->assertDatabaseHas('support_tiers', [
            'code' => 'SUPPORT-PRIORITY',
            'monthly_fee' => '35000.00',
            'included_hours' => 20,
            'response_sla_hours' => 24,
        ]);
    }

    public function test_admin_can_list_products(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonFragment(['code' => 'SCALELENS'])
            ->assertJsonPath('options.statuses.0.value', 'active');
    }

    public function test_admin_can_create_product(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')->postJson('/api/v1/products', [
            'code' => 'SCALEDESK',
            'name' => 'ScaleDesk',
            'description' => 'Support desk platform.',
        ]);

        $response->assertCreated()->assertJsonFragment(['code' => 'SCALEDESK']);
        $this->assertDatabaseHas('products', ['code' => 'SCALEDESK', 'status' => 'active']);
    }

    public function test_product_code_must_be_unique(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/products', ['code' => 'SCALELENS', 'name' => 'Duplicate'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_admin_can_create_package_under_product(): void
    {
        $this->seed();

        $product = Product::where('code', 'SCALELENS')->firstOrFail();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/packages", [
                'code' => 'SCALELENS-CUSTOM',
                'name' => 'Custom',
                'billing_interval' => 'once_off',
                'price_min' => 150000,
                'price_max' => 250000,
            ]);

        $response->assertCreated()->assertJsonFragment(['code' => 'SCALELENS-CUSTOM']);
        $this->assertDatabaseHas('packages', [
            'code' => 'SCALELENS-CUSTOM',
            'product_id' => $product->id,
            'billing_interval' => 'once_off',
        ]);
    }

    public function test_package_price_max_cannot_be_below_price_min(): void
    {
        $this->seed();

        $product = Product::where('code', 'SCALELENS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/packages", [
                'code' => 'SCALELENS-BAD',
                'name' => 'Bad range',
                'billing_interval' => 'once_off',
                'price_min' => 300000,
                'price_max' => 100000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price_max');
    }

    public function test_admin_can_list_catalogue_features(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/catalogue-features');

        $response->assertOk()
            ->assertJsonFragment(['code' => 'SCALELENS-FEATURE-RISKS'])
            ->assertJsonPath('options.statuses.0.value', 'active');
    }

    public function test_admin_can_create_catalogue_feature_under_product(): void
    {
        $this->seed();

        $product = Product::where('code', 'SCALELENS')->firstOrFail();
        $starter = Package::where('code', 'SCALELENS-STARTER')->firstOrFail();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/catalogue-features", [
                'minimum_package_id' => $starter->id,
                'code' => 'SCALELENS-FEATURE-CLIENT-PORTAL',
                'name' => 'Client portal',
            ]);

        $response->assertCreated()->assertJsonFragment(['code' => 'SCALELENS-FEATURE-CLIENT-PORTAL']);
        $this->assertDatabaseHas('catalogue_features', [
            'code' => 'SCALELENS-FEATURE-CLIENT-PORTAL',
            'product_id' => $product->id,
            'minimum_package_id' => $starter->id,
        ]);
    }

    public function test_feature_minimum_package_must_belong_to_selected_product(): void
    {
        $this->seed();

        $scaleLens = Product::where('code', 'SCALELENS')->firstOrFail();
        $otherProduct = Product::create(['code' => 'OTHER', 'name' => 'Other', 'status' => 'active']);
        $otherPackage = Package::create([
            'product_id' => $otherProduct->id,
            'code' => 'OTHER-STARTER',
            'name' => 'Other Starter',
            'billing_interval' => 'once_off',
            'price_min' => 1000,
            'currency' => 'ZAR',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/products/{$scaleLens->id}/catalogue-features", [
                'minimum_package_id' => $otherPackage->id,
                'code' => 'SCALELENS-FEATURE-BAD',
                'name' => 'Bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('minimum_package_id');
    }

    public function test_feature_product_update_cannot_leave_cross_product_minimum_package(): void
    {
        $this->seed();

        $feature = CatalogueFeature::where('code', 'SCALELENS-FEATURE-RISKS')->firstOrFail();
        $otherProduct = Product::create(['code' => 'OTHER', 'name' => 'Other', 'status' => 'active']);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/catalogue-features/{$feature->id}", [
                'product_id' => $otherProduct->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('minimum_package_id');
    }

    public function test_admin_can_list_support_tiers(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/support-tiers');

        $response->assertOk()
            ->assertJsonFragment(['code' => 'SUPPORT-STRATEGIC'])
            ->assertJsonPath('options.default_currency', 'ZAR');
    }

    public function test_admin_can_create_support_tier(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/support-tiers', [
                'code' => 'SUPPORT-CUSTOM',
                'name' => 'Custom',
                'monthly_fee' => 75000,
                'included_hours' => 50,
                'response_sla_hours' => 4,
            ]);

        $response->assertCreated()->assertJsonFragment(['code' => 'SUPPORT-CUSTOM']);
        $this->assertDatabaseHas('support_tiers', [
            'code' => 'SUPPORT-CUSTOM',
            'monthly_fee' => '75000.00',
            'included_hours' => 50,
            'response_sla_hours' => 4,
        ]);
    }

    public function test_admin_can_archive_product(): void
    {
        $this->seed();

        $product = Product::where('code', 'SCALELENS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $this->seed();

        $this->actingAs($this->finance(), 'sanctum')
            ->postJson('/api/v1/products', ['code' => 'NOPE', 'name' => 'Nope'])
            ->assertForbidden();
    }
}
