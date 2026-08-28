<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentCommentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function incident(): Incident
    {
        return Incident::where('reference', 'INC-2001')->firstOrFail();
    }

    public function test_comments_list_starts_empty(): void
    {
        $this->seed();

        $this->actingAs($this->user('admin@codescaletech.test'), 'sanctum')
            ->getJson("/api/v1/incidents/{$this->incident()->id}/comments")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_permitted_user_can_post_a_comment(): void
    {
        $this->seed();

        $incident = $this->incident();
        $operations = $this->user('operations@codescaletech.test');

        $this->actingAs($operations, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/comments", ['body' => 'Confirmed backup job restarted cleanly.'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Confirmed backup job restarted cleanly.')
            ->assertJsonPath('data.author_name', $operations->name);

        $this->assertDatabaseHas('incident_comments', [
            'incident_id' => $incident->id,
            'user_id' => $operations->id,
        ]);

        $this->actingAs($operations, 'sanctum')
            ->getJson("/api/v1/incidents/{$incident->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_comment_requires_a_body(): void
    {
        $this->seed();

        $this->actingAs($this->user('operations@codescaletech.test'), 'sanctum')
            ->postJson("/api/v1/incidents/{$this->incident()->id}/comments", ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_viewer_without_comment_permission_cannot_post(): void
    {
        $this->seed();

        // Executive can view incidents but lacks incidents.comment.
        $this->actingAs($this->user('executive@codescaletech.test'), 'sanctum')
            ->postJson("/api/v1/incidents/{$this->incident()->id}/comments", ['body' => 'Nope.'])
            ->assertForbidden();
    }

    public function test_posting_a_comment_is_recorded_in_the_activity_feed(): void
    {
        $this->seed();

        $incident = $this->incident();

        $this->actingAs($this->user('operations@codescaletech.test'), 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/comments", ['body' => 'Logged a note.'])
            ->assertCreated();

        $this->actingAs($this->user('admin@codescaletech.test'), 'sanctum')
            ->getJson("/api/v1/activities?subject_type=Incident&subject_id={$incident->id}")
            ->assertOk()
            ->assertJsonFragment(['event' => 'commented']);
    }

    public function test_show_returns_incident_detail(): void
    {
        $this->seed();

        $this->actingAs($this->user('admin@codescaletech.test'), 'sanctum')
            ->getJson("/api/v1/incidents/{$this->incident()->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', 'INC-2001');
    }
}
