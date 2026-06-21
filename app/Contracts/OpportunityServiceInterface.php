<?php

namespace App\Contracts;

use App\Models\Opportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OpportunityServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $stage = null, ?string $clientId = null, ?int $ownerId = null): LengthAwarePaginator;

    public function create(array $data): Opportunity;

    public function update(Opportunity $opportunity, array $data): Opportunity;

    public function delete(Opportunity $opportunity): void;

    /**
     * @return array<string, mixed>
     */
    public function summary(): array;

    /**
     * Per-stage count and value for the pipeline chart.
     *
     * @return list<array{stage: string, label: string, count: int, value: float}>
     */
    public function pipeline(): array;

    /**
     * Mark an opportunity Won and, when it has a client, create a linked draft contract.
     */
    public function markWon(Opportunity $opportunity): Opportunity;
}
