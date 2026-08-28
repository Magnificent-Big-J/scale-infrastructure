<?php

namespace Tests\Feature;

use App\Enums\IncidentStatus;
use App\Enums\MonitoringCheckStatus;
use App\Models\Incident;
use App\Models\MonitoringCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringCheckIncidentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function operations(): User
    {
        return User::where('email', 'operations@codescaletech.test')->firstOrFail();
    }

    private function payload(MonitoringCheck $check, MonitoringCheckStatus $status): array
    {
        return [
            'name' => $check->name,
            'check_type' => $check->check_type,
            'target' => $check->target,
            'status' => $status->value,
        ];
    }

    public function test_a_check_transitioning_to_failing_opens_an_incident(): void
    {
        $this->seed();
        $check = MonitoringCheck::where('status', MonitoringCheckStatus::Passing)->firstOrFail();

        $this->actingAs($this->operations(), 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Failing))
            ->assertOk();

        $incident = Incident::where('monitoring_check_id', $check->id)->firstOrFail();
        $this->assertSame(IncidentStatus::Open, $incident->status);
        $this->assertSame($check->deployment->client_id, $incident->client_id);
        $this->assertSame($check->deployment_id, $incident->deployment_id);
    }

    public function test_a_check_flapping_while_already_failing_does_not_open_a_duplicate_incident(): void
    {
        $this->seed();
        $check = MonitoringCheck::where('status', MonitoringCheckStatus::Passing)->firstOrFail();
        $user = $this->operations();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Failing))
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Warning))
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Failing))
            ->assertOk();

        $this->assertSame(1, Incident::where('monitoring_check_id', $check->id)->count());
    }

    public function test_a_check_recovering_resolves_its_open_incident(): void
    {
        $this->seed();
        $check = MonitoringCheck::where('status', MonitoringCheckStatus::Passing)->firstOrFail();
        $user = $this->operations();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Failing))
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Passing))
            ->assertOk();

        $incident = Incident::where('monitoring_check_id', $check->id)->firstOrFail();
        $this->assertSame(IncidentStatus::Resolved, $incident->status);
        $this->assertNotNull($incident->resolved_at);
    }

    public function test_updating_a_check_without_a_failing_transition_does_not_touch_incidents(): void
    {
        $this->seed();
        $check = MonitoringCheck::where('status', MonitoringCheckStatus::Passing)->firstOrFail();

        $this->actingAs($this->operations(), 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check->id}", $this->payload($check, MonitoringCheckStatus::Passing))
            ->assertOk();

        $this->assertSame(0, Incident::where('monitoring_check_id', $check->id)->count());
    }
}
