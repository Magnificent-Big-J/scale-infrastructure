<?php

namespace Tests\Feature;

use App\Models\Product;
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
