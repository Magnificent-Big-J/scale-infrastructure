<?php

namespace App\Services;

use App\Contracts\TicketCommentServiceInterface;
use App\Models\SupportTicket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TicketCommentService implements TicketCommentServiceInterface
{
    public function forTicket(SupportTicket $ticket): Collection
    {
        return $ticket->comments()->with('author')->get();
    }

    public function create(SupportTicket $ticket, User $author, array $data): TicketComment
    {
        return DB::transaction(function () use ($ticket, $author, $data) {
            $comment = $ticket->comments()->create([
                'user_id' => $author->id,
                'body' => $data['body'],
                'is_internal' => $data['is_internal'] ?? true,
            ]);

            $this->log($ticket, $author, $comment);

            return $comment->load('author');
        });
    }

    private function log(SupportTicket $ticket, User $author, TicketComment $comment): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('support_tickets')
            ->performedOn($ticket)
            ->causedBy($author)
            ->withProperties(['comment_id' => $comment->id])
            ->event('commented')
            ->log('Added a ticket comment');
    }
}
