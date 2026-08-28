<?php

namespace App\Services;

use App\Contracts\IncidentCommentServiceInterface;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IncidentCommentService implements IncidentCommentServiceInterface
{
    public function forIncident(Incident $incident): Collection
    {
        return $incident->comments()->with('author')->get();
    }

    public function create(Incident $incident, User $author, array $data): IncidentComment
    {
        return DB::transaction(function () use ($incident, $author, $data) {
            $comment = $incident->comments()->create([
                'user_id' => $author->id,
                'body' => $data['body'],
            ]);

            $this->log($incident, $author, $comment);

            return $comment->load('author');
        });
    }

    private function log(Incident $incident, User $author, IncidentComment $comment): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('incidents')
            ->performedOn($incident)
            ->causedBy($author)
            ->withProperties(['comment_id' => $comment->id])
            ->event('commented')
            ->log('Added an incident comment');
    }
}
