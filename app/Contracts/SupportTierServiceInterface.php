<?php

namespace App\Contracts;

use App\Models\SupportTier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupportTierServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    public function create(array $data): SupportTier;

    public function update(SupportTier $supportTier, array $data): SupportTier;

    public function archive(SupportTier $supportTier): void;
}
