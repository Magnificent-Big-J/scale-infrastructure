<?php

namespace App\Contracts;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface IncidentCommentServiceInterface
{
    /**
     * @return Collection<int, IncidentComment>
     */
    public function forIncident(Incident $incident): Collection;

    public function create(Incident $incident, User $author, array $data): IncidentComment;
}
