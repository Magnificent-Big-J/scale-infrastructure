<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_seeder_creates_a_pipeline(): void
    {
        $this->seed();

        $this->assertSame(6, Opportunity::count());
        $this->assertDatabaseHas('opportunities', ['title' => 'Greenfield ScaleLens build', 'client_id' => null]);
    }

    public function test_index_returns_summary_pipeline_and_options(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/opportunities')
            ->assertOk()
            ->assertJsonStructure([
                'summary' => ['open_count', 'open_value', 'won_count', 'won_value', 'win_rate'],
                'pipeline' => [['stage', 'label', 'count', 'value']],
                'options' => ['stages', 'sources', 'clients', 'owners'],
            ])
            ->assertJsonPath('pipeline.0.stage', 'lead');
    }

    public function test_can_create_opportunity_for_a_client(): void
    {
        $this->seed();
        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/opportunities', [
                'client_id' => $client->id,
                'title' => 'New retainer expansion',
                'stage' => 'qualified',
                'value' => 150000,
                'source' => 'existing_client',
            ])
            ->assertCreated()
            ->assertJsonFragment(['title' => 'New retainer expansion']);
    }

    public function test_can_create_opportunity_for_a_prospect(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/opportunities', [
                'prospect_name' => 'Acme Startups',
                'title' => 'Greenfield build',
                'value' => 400000,
            ])
            ->assertCreated()
            ->assertJsonPath('display_name', 'Acme Startups');
    }

    public function test_requires_a_client_or_prospect(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/opportunities', ['title' => 'Floating opportunity', 'value' => 1000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client_id', 'prospect_name']);
    }

    public function test_moving_to_won_stamps_won_at(): void
    {
        $this->seed();
        $opportunity = Opportunity::where('title', 'Second environment rollout')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/opportunities/{$opportunity->id}", ['stage' => 'won'])
            ->assertOk()
            ->assertJsonPath('data.stage', 'won')
            ->assertJsonPath('data.won_at', fn ($value) => $value !== null);
    }

    public function test_win_action_creates_a_linked_draft_contract(): void
    {
        $this->seed();
        $opportunity = Opportunity::where('title', 'Production observability uplift')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/opportunities/{$opportunity->id}/win")
            ->assertOk()
            ->assertJsonPath('data.stage', 'won')
            ->assertJsonPath('data.contract_id', fn ($value) => $value !== null);

        $opportunity->refresh();

        $this->assertDatabaseHas('contracts', [
            'id' => $opportunity->contract_id,
            'name' => 'Production observability uplift',
            'status' => 'draft',
            'client_id' => $opportunity->client_id,
        ]);
    }

    public function test_winning_a_prospect_opportunity_makes_no_contract(): void
    {
        $this->seed();
        $opportunity = Opportunity::where('title', 'Greenfield ScaleLens build')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/opportunities/{$opportunity->id}/win")
            ->assertOk()
            ->assertJsonPath('data.stage', 'won')
            ->assertJsonPath('data.contract_id', null);
    }

    public function test_user_without_opportunity_permission_is_forbidden(): void
    {
        $this->seed();

        // Operations role has no opportunities.* permissions.
        $operations = User::where('email', 'operations@codescaletech.test')->firstOrFail();

        $this->actingAs($operations, 'sanctum')
            ->getJson('/api/v1/opportunities')
            ->assertForbidden();
    }
}
