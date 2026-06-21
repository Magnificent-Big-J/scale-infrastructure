<?php

namespace App\Contracts;

use App\Models\Incident;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupportOperationsServiceInterface
{
    public function paginateAgreements(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $clientId = null): LengthAwarePaginator;

    public function paginateTickets(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null, ?string $clientId = null): LengthAwarePaginator;

    /**
     * @return list<array{period: string, opened: int, resolved: int}>
     */
    public function throughput(int $months = 6): array;

    public function paginateIncidents(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null): LengthAwarePaginator;

    public function createAgreement(array $data): SupportAgreement;

    public function updateAgreement(SupportAgreement $agreement, array $data): SupportAgreement;

    public function createTicket(array $data): SupportTicket;

    public function updateTicket(SupportTicket $ticket, array $data): SupportTicket;

    public function createIncident(array $data): Incident;

    public function updateIncident(Incident $incident, array $data): Incident;
}
