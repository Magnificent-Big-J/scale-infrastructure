<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function ticket(): SupportTicket
    {
        return SupportTicket::where('reference', 'TCK-1001')->firstOrFail();
    }

    public function test_comments_list_starts_empty(): void
    {
        $this->seed();

        $this->actingAs($this->user('admin@codescaletech.test'), 'sanctum')
            ->getJson("/api/v1/support-tickets/{$this->ticket()->id}/comments")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_permitted_user_can_post_a_comment(): void
    {
        $this->seed();

        $ticket = $this->ticket();
        $support = $this->user('support@codescaletech.test');

        $this->actingAs($support, 'sanctum')
            ->postJson("/api/v1/support-tickets/{$ticket->id}/comments", ['body' => 'Investigating the export spacing now.'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Investigating the export spacing now.')
            ->assertJsonPath('data.author_name', $support->name);

        $this->assertDatabaseHas('ticket_comments', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $support->id,
            'is_internal' => true,
        ]);

        $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/support-tickets/{$ticket->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_comment_requires_a_body(): void
    {
        $this->seed();

        $this->actingAs($this->user('support@codescaletech.test'), 'sanctum')
            ->postJson("/api/v1/support-tickets/{$this->ticket()->id}/comments", ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_viewer_without_comment_permission_cannot_post(): void
    {
        $this->seed();

        // Executive can view tickets but lacks support_tickets.comment.
        $this->actingAs($this->user('executive@codescaletech.test'), 'sanctum')
            ->postJson("/api/v1/support-tickets/{$this->ticket()->id}/comments", ['body' => 'Nope.'])
            ->assertForbidden();
    }

    public function test_posting_a_comment_is_recorded_in_the_activity_feed(): void
    {
        $this->seed();

        $ticket = $this->ticket();

        $this->actingAs($this->user('support@codescaletech.test'), 'sanctum')
            ->postJson("/api/v1/support-tickets/{$ticket->id}/comments", ['body' => 'Logged a note.'])
            ->assertCreated();

        $this->actingAs($this->user('admin@codescaletech.test'), 'sanctum')
            ->getJson("/api/v1/activities?subject_type=SupportTicket&subject_id={$ticket->id}")
            ->assertOk()
            ->assertJsonFragment(['event' => 'commented']);
    }
}
