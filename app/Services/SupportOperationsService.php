<?php

namespace App\Services;

use App\Contracts\SupportOperationsServiceInterface;
use App\Enums\IncidentStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Incident;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupportOperationsService implements SupportOperationsServiceInterface
{
    public function paginateAgreements(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $clientId = null): LengthAwarePaginator
    {
        return SupportAgreement::query()
            ->with(['client', 'supportTier'])
            ->withCount('tickets')
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateTickets(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null, ?string $clientId = null): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->with(['client', 'deployment', 'supportAgreement', 'assignedUser'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($severity, fn ($query) => $query->where('severity', $severity))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw("case when severity = 'critical' then 0 when severity = 'high' then 1 else 2 end")
            ->latest('opened_at')
            ->paginate($perPage);
    }

    public function paginateIncidents(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null): LengthAwarePaginator
    {
        return Incident::query()
            ->with(['client', 'deployment'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($severity, fn ($query) => $query->where('severity', $severity))
            ->orderByRaw("case when severity = 'critical' then 0 when severity = 'high' then 1 else 2 end")
            ->latest('started_at')
            ->paginate($perPage);
    }

    public function createAgreement(array $data): SupportAgreement
    {
        return DB::transaction(function () use ($data) {
            $agreement = SupportAgreement::query()->create($data);

            $this->log('support_agreements', $agreement, 'created', 'Created support agreement');

            return $agreement->load(['client', 'supportTier'])->loadCount('tickets');
        });
    }

    public function updateAgreement(SupportAgreement $agreement, array $data): SupportAgreement
    {
        return DB::transaction(function () use ($agreement, $data) {
            $agreement->fill($data)->save();

            $this->log('support_agreements', $agreement, 'updated', 'Updated support agreement', ['changes' => array_keys($agreement->getChanges())]);

            return $agreement->refresh()->load(['client', 'supportTier'])->loadCount('tickets');
        });
    }

    public function createTicket(array $data): SupportTicket
    {
        return DB::transaction(function () use ($data) {
            $ticket = SupportTicket::query()->create($data);

            $this->log('support_tickets', $ticket, 'created', 'Logged support ticket');

            return $ticket->load(['client', 'deployment', 'supportAgreement', 'assignedUser']);
        });
    }

    public function updateTicket(SupportTicket $ticket, array $data): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $ticket->fill($data);

            if (in_array($ticket->status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true) && $ticket->resolved_at === null) {
                $ticket->resolved_at = now();
            }

            $ticket->save();

            $this->log('support_tickets', $ticket, 'updated', 'Updated support ticket', ['changes' => array_keys($ticket->getChanges())]);

            return $ticket->refresh()->load(['client', 'deployment', 'supportAgreement', 'assignedUser']);
        });
    }

    public function createIncident(array $data): Incident
    {
        return DB::transaction(function () use ($data) {
            $incident = Incident::query()->create($data);

            $this->log('incidents', $incident, 'created', 'Logged incident');

            return $incident->load(['client', 'deployment']);
        });
    }

    public function updateIncident(Incident $incident, array $data): Incident
    {
        return DB::transaction(function () use ($incident, $data) {
            $incident->fill($data);

            if (in_array($incident->status, [IncidentStatus::Resolved, IncidentStatus::Closed], true) && $incident->resolved_at === null) {
                $incident->resolved_at = now();
            }

            $incident->save();

            $this->log('incidents', $incident, 'updated', 'Updated incident', ['changes' => array_keys($incident->getChanges())]);

            return $incident->refresh()->load(['client', 'deployment']);
        });
    }

    private function log(string $domain, Model $subject, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity($domain)
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
