<?php

namespace App\Services;

use App\Contracts\SupportTierServiceInterface;
use App\Models\SupportTier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupportTierService implements SupportTierServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return SupportTier::query()
            ->search($search)
            ->withStatus($status)
            ->orderBy('sort_order')
            ->orderBy('monthly_fee')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): SupportTier
    {
        return DB::transaction(function () use ($data) {
            $supportTier = SupportTier::query()->create($data);

            $this->log($supportTier, 'created', 'Created support tier');

            return $supportTier;
        });
    }

    public function update(SupportTier $supportTier, array $data): SupportTier
    {
        return DB::transaction(function () use ($supportTier, $data) {
            $supportTier->fill($data);
            $supportTier->save();

            $this->log($supportTier, 'updated', 'Updated support tier', ['changes' => array_keys($supportTier->getChanges())]);

            return $supportTier->refresh();
        });
    }

    public function archive(SupportTier $supportTier): void
    {
        DB::transaction(function () use ($supportTier) {
            $supportTier->delete();

            $this->log($supportTier, 'archived', 'Archived support tier');
        });
    }

    private function log(SupportTier $supportTier, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity($supportTier->getTable())
            ->performedOn($supportTier)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
