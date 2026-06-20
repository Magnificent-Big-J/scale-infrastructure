<?php

namespace App\Contracts;

use App\Models\ProfitabilityRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProfitabilityServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $period = null, ?string $clientId = null): LengthAwarePaginator;

    public function create(array $data): ProfitabilityRecord;

    public function update(ProfitabilityRecord $record, array $data): ProfitabilityRecord;

    /**
     * @return array<string, mixed>
     */
    public function summary(): array;
}
