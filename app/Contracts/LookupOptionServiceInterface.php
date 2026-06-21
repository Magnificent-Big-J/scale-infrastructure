<?php

namespace App\Contracts;

use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LookupOptionServiceInterface
{
    public function paginate(int $perPage = 15, ?string $type = null, ?string $search = null): LengthAwarePaginator;

    /**
     * Active, ordered options for a single list — feeds selects on forms.
     *
     * @return Collection<int, LookupOption>
     */
    public function optionsFor(LookupType $type): Collection;

    public function create(array $data): LookupOption;

    public function update(LookupOption $option, array $data): LookupOption;

    public function delete(LookupOption $option): void;
}
