<?php

namespace App\Services;

use App\Contracts\SupportOperationsServiceInterface;
use App\Models\Incident;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupportOperationsService implements SupportOperationsServiceInterface
{
    public function paginateAgreements(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return SupportAgreement::query()
            ->with(['client', 'supportTier'])
            ->withCount('tickets')
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateTickets(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->with(['client', 'deployment', 'supportAgreement', 'assignedUser'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($severity, fn ($query) => $query->where('severity', $severity))
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
        return DB::transaction(fn () => SupportAgreement::query()->create($data)->load(['client', 'supportTier'])->loadCount('tickets'));
    }

    public function createTicket(array $data): SupportTicket
    {
        return DB::transaction(fn () => SupportTicket::query()->create($data)->load(['client', 'deployment', 'supportAgreement', 'assignedUser']));
    }

    public function createIncident(array $data): Incident
    {
        return DB::transaction(fn () => Incident::query()->create($data)->load(['client', 'deployment']));
    }
}
