<?php

namespace Tests\Feature;

use App\Enums\SlaStatus;
use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function ticketWithAgreement(): SupportTicket
    {
        return SupportTicket::query()->whereNotNull('support_agreement_id')->firstOrFail()->load('supportAgreement');
    }

    public function test_open_overdue_ticket_is_breached(): void
    {
        $this->seed();

        $ticket = $this->ticketWithAgreement();
        $ticket->status = SupportTicketStatus::Open;
        $ticket->opened_at = Carbon::now()->subDays(30);

        $this->assertSame(SlaStatus::Breached, $ticket->slaSnapshot()['status']);
    }

    public function test_recently_opened_ticket_is_on_track(): void
    {
        $this->seed();

        $ticket = $this->ticketWithAgreement();
        $ticket->status = SupportTicketStatus::Open;
        $ticket->severity = SupportSeverity::Low; // widest window
        $ticket->opened_at = Carbon::now();

        $this->assertSame(SlaStatus::OnTrack, $ticket->slaSnapshot()['status']);
    }

    public function test_quickly_resolved_ticket_is_met(): void
    {
        $this->seed();

        $ticket = $this->ticketWithAgreement();
        $ticket->status = SupportTicketStatus::Resolved;
        $ticket->opened_at = Carbon::now()->subHours(1);
        $ticket->resolved_at = Carbon::now()->subMinutes(30);

        $this->assertSame(SlaStatus::Met, $ticket->slaSnapshot()['status']);
    }

    public function test_ticket_without_agreement_has_no_sla(): void
    {
        $this->seed();

        $ticket = SupportTicket::query()->firstOrFail();
        $ticket->setRelation('supportAgreement', null);

        $this->assertSame(SlaStatus::None, $ticket->slaSnapshot()['status']);
    }

    public function test_sla_endpoint_returns_summary_and_fields(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/support/sla')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'reference', 'sla_status', 'sla_status_label', 'sla_status_color']],
                'summary' => ['breached', 'at_risk', 'on_track', 'met'],
            ]);
    }
}
