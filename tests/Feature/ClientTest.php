<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
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

    public function test_client_seeder_creates_clients_and_primary_contacts(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();

        $this->assertSame(3, Client::count());
        $this->assertSame('enterprise', $client->tier);
        $this->assertSame('Thandi Mbeki', $client->primaryContact()->first()?->name);
        $this->assertSame(1, $client->contacts()->where('is_primary', true)->count());
    }

    public function test_admin_can_list_clients(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonFragment(['code' => 'NALA-PROJECTS'])
            ->assertJsonPath('options.statuses.0.value', 'prospect');
    }

    public function test_admin_can_create_client_with_primary_contact(): void
    {
        $this->seed();

        $package = Package::where('code', 'SCALELENS-STARTER')->firstOrFail();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/clients', [
                'package_id' => $package->id,
                'code' => 'UMOYA-PMO',
                'name' => 'Umoya PMO',
                'tier' => 'starter',
                'status' => 'prospect',
                'health_score' => 68,
                'primary_contact' => [
                    'name' => 'Nomsa Khumalo',
                    'email' => 'nomsa.khumalo@example.test',
                    'role' => 'PMO Lead',
                ],
            ]);

        $response->assertCreated()->assertJsonFragment(['code' => 'UMOYA-PMO']);
        $this->assertDatabaseHas('clients', ['code' => 'UMOYA-PMO']);
        $this->assertDatabaseHas('contacts', [
            'email' => 'nomsa.khumalo@example.test',
            'is_primary' => true,
        ]);
    }

    public function test_marking_new_contact_primary_clears_previous_primary_contact(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/clients/{$client->id}/contacts", [
                'name' => 'New Primary',
                'email' => 'new.primary@example.test',
                'is_primary' => true,
            ])
            ->assertCreated();

        $this->assertSame(1, Contact::where('client_id', $client->id)->where('is_primary', true)->count());
        $this->assertDatabaseHas('contacts', [
            'client_id' => $client->id,
            'email' => 'new.primary@example.test',
            'is_primary' => true,
        ]);
    }

    public function test_non_admin_without_client_create_permission_cannot_create_client(): void
    {
        $this->seed();

        $this->actingAs($this->finance(), 'sanctum')
            ->postJson('/api/v1/clients', [
                'code' => 'NOPE',
                'name' => 'Nope',
                'tier' => 'starter',
                'status' => 'prospect',
                'health_score' => 50,
            ])
            ->assertForbidden();
    }
}
