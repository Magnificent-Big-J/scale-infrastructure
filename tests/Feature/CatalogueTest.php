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
        $this->assertDatabaseHas('packages', ['code' => 'SCALELENS-GROWTH', 'product_id' => $product->id]);
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
                'code' => 'SCALELENS-ENTERPRISE',
                'name' => 'Enterprise',
                'billing_interval' => 'annual',
                'price' => 150000,
            ]);

        $response->assertCreated()->assertJsonFragment(['code' => 'SCALELENS-ENTERPRISE']);
        $this->assertDatabaseHas('packages', [
            'code' => 'SCALELENS-ENTERPRISE',
            'product_id' => $product->id,
            'billing_interval' => 'annual',
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
