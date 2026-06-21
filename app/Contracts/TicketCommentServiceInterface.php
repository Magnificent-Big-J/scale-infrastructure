<?php

namespace App\Contracts;

use App\Models\SupportTicket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TicketCommentServiceInterface
{
    /**
     * @return Collection<int, TicketComment>
     */
    public function forTicket(SupportTicket $ticket): Collection;

    public function create(SupportTicket $ticket, User $author, array $data): TicketComment;
}
