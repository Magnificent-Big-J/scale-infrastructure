<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
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

    /**
     * Creating through a service records an activity row the feed then returns.
     */
    private function createProduct(): Product
    {
        $this->actingAs($this->admin(), 'sanctum')->postJson('/api/v1/products', [
            'code' => 'ACTLOG',
            'name' => 'Activity Log Product',
        ])->assertCreated();

        return Product::where('code', 'ACTLOG')->firstOrFail();
    }

    public function test_feed_returns_recorded_activity(): void
    {
        $this->seed();
        $this->createProduct();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/activities')
            ->assertOk()
            ->assertJsonFragment(['log_name' => 'products', 'event' => 'created'])
            ->assertJsonFragment(['description' => 'Created product']);
    }

    public function test_feed_can_filter_by_subject(): void
    {
        $this->seed();
        $product = $this->createProduct();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/activities?subject_type=Product&subject_id={$product->id}")
            ->assertOk()
            ->assertJsonFragment(['log_name' => 'products'])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_feed_requires_activity_view_permission(): void
    {
        $this->seed();

        $this->actingAs($this->finance(), 'sanctum')
            ->getJson('/api/v1/activities')
            ->assertForbidden();
    }

    public function test_executive_dashboard_exposes_status_distributions(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/dashboard/executive')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'distributions' => [
                        'deployments_by_status' => ['labels', 'series'],
                        'tickets_by_status' => ['labels', 'series'],
                    ],
                ],
            ]);
    }
}
