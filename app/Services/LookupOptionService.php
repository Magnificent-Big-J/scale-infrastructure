<?php

namespace App\Services;

use App\Contracts\LookupOptionServiceInterface;
use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LookupOptionService implements LookupOptionServiceInterface
{
    public function paginate(int $perPage = 15, ?string $type = null, ?string $search = null): LengthAwarePaginator
    {
        return LookupOption::query()
            ->ofType($type)
            ->search($search)
            ->ordered()
            ->paginate($perPage);
    }

    public function optionsFor(LookupType $type): Collection
    {
        return LookupOption::query()
            ->ofType($type)
            ->active()
            ->ordered()
            ->get();
    }

    public function create(array $data): LookupOption
    {
        return DB::transaction(function () use ($data) {
            $option = LookupOption::query()->create($data);

            $this->log($option, 'created', 'Created reference data option');

            return $option;
        });
    }

    public function update(LookupOption $option, array $data): LookupOption
    {
        return DB::transaction(function () use ($option, $data) {
            $option->fill($data);
            $option->save();

            $this->log($option, 'updated', 'Updated reference data option', ['changes' => array_keys($option->getChanges())]);

            return $option->refresh();
        });
    }

    public function delete(LookupOption $option): void
    {
        DB::transaction(function () use ($option) {
            $option->delete();

            $this->log($option, 'deleted', 'Deleted reference data option');
        });
    }

    private function log(LookupOption $option, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity($option->getTable())
            ->performedOn($option)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
