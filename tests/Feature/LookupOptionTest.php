<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LookupOption;
use App\Models\SupportAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupOptionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function support(): User
    {
        return User::where('email', 'support@codescaletech.test')->firstOrFail();
    }

    public function test_seeder_creates_ticket_category_options(): void
    {
        $this->seed();

        $this->assertDatabaseHas('lookup_options', [
            'type' => 'ticket_category',
            'value' => 'reporting',
            'is_active' => true,
        ]);
        $this->assertSame(9, LookupOption::where('type', 'ticket_category')->count());
    }

    public function test_admin_can_list_reference_data(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/reference-data?type=ticket_category')
            ->assertOk()
            ->assertJsonFragment(['value' => 'reporting', 'type' => 'ticket_category'])
            ->assertJsonPath('options.types.0.value', 'ticket_category');
    }

    public function test_admin_can_create_reference_option(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/reference-data', [
                'type' => 'ticket_category',
                'value' => 'onboarding',
                'label' => 'Onboarding',
                'sort_order' => 20,
            ])
            ->assertCreated()
            ->assertJsonFragment(['value' => 'onboarding', 'label' => 'Onboarding']);

        $this->assertDatabaseHas('lookup_options', ['type' => 'ticket_category', 'value' => 'onboarding']);
    }

    public function test_value_must_be_unique_within_a_type(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/reference-data', [
                'type' => 'ticket_category',
                'value' => 'reporting',
                'label' => 'Duplicate',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');
    }

    public function test_value_must_be_a_valid_slug(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/reference-data', [
                'type' => 'ticket_category',
                'value' => 'Has Spaces',
                'label' => 'Bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/reference-data', [
                'type' => 'not_a_real_list',
                'value' => 'whatever',
                'label' => 'Nope',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_admin_can_update_and_delete_option(): void
    {
        $this->seed();

        $option = LookupOption::where('type', 'ticket_category')->where('value', 'billing')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/reference-data/{$option->id}", ['label' => 'Billing & finance', 'is_active' => false])
            ->assertOk()
            ->assertJsonFragment(['label' => 'Billing & finance', 'is_active' => false]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/reference-data/{$option->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('lookup_options', ['id' => $option->id]);
    }

    public function test_lookup_feed_returns_only_active_options(): void
    {
        $this->seed();

        LookupOption::where('type', 'ticket_category')->where('value', 'security')->update(['is_active' => false]);

        $response = $this->actingAs($this->support(), 'sanctum')
            ->getJson('/api/v1/lookups/ticket_category')
            ->assertOk()
            ->assertJsonFragment(['value' => 'reporting', 'label' => 'Reporting'])
            ->assertJsonMissing(['value' => 'security']);

        // Ordered by sort_order — the first seeded option leads.
        $this->assertSame('general', $response->json('data.0.value'));
    }

    public function test_lookup_feed_404s_for_unknown_type(): void
    {
        $this->seed();

        $this->actingAs($this->support(), 'sanctum')
            ->getJson('/api/v1/lookups/not_a_real_list')
            ->assertNotFound();
    }

    public function test_non_admin_cannot_manage_reference_data(): void
    {
        $this->seed();

        $this->actingAs($this->support(), 'sanctum')
            ->postJson('/api/v1/reference-data', [
                'type' => 'ticket_category',
                'value' => 'sneaky',
                'label' => 'Sneaky',
            ])
            ->assertForbidden();
    }

    public function test_support_ticket_rejects_unmanaged_category(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();
        $agreement = SupportAgreement::where('code', 'AGR-NALA-PRIORITY')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/support-tickets', [
                'client_id' => $client->id,
                'support_agreement_id' => $agreement->id,
                'reference' => 'TCK-9001',
                'subject' => 'Unmanaged category',
                'category' => 'totally-made-up',
                'severity' => 'low',
                'status' => 'open',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_support_ticket_rejects_inactive_category(): void
    {
        $this->seed();

        LookupOption::where('type', 'ticket_category')->where('value', 'security')->update(['is_active' => false]);

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/support-tickets', [
                'client_id' => $client->id,
                'reference' => 'TCK-9002',
                'subject' => 'Inactive category',
                'category' => 'security',
                'severity' => 'low',
                'status' => 'open',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_support_ticket_index_exposes_category_options(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/support-tickets')
            ->assertOk()
            ->assertJsonFragment(['value' => 'reporting', 'label' => 'Reporting']);
    }
}
