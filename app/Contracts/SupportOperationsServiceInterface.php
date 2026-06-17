<?php

namespace App\Contracts;

use App\Models\Incident;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupportOperationsServiceInterface
{
    public function paginateAgreements(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    public function paginateTickets(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null): LengthAwarePaginator;

    public function paginateIncidents(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $severity = null): LengthAwarePaginator;

    public function createAgreement(array $data): SupportAgreement;

    public function createTicket(array $data): SupportTicket;

    public function createIncident(array $data): Incident;
}
