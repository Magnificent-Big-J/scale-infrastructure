<?php

namespace App\Services;

use App\Contracts\ActivityFeedServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityFeedService implements ActivityFeedServiceInterface
{
    /**
     * Models exposed to the timeline are resolved from a short name (e.g.
     * "Client") to the fully-qualified subject_type the log stores.
     */
    private const MODEL_NAMESPACE = 'App\\Models\\';

    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        return Activity::query()
            ->with('causer')
            ->when($filters['log_name'] ?? null, fn (Builder $query, string $log) => $query->where('log_name', $log))
            ->when($filters['subject_type'] ?? null, fn (Builder $query, string $type) => $query->where('subject_type', $this->resolveSubjectType($type)))
            ->when($filters['subject_id'] ?? null, fn (Builder $query, string $id) => $query->where('subject_id', $id))
            ->when($filters['causer_id'] ?? null, fn (Builder $query, string $id) => $query->where('causer_id', $id))
            ->latest()
            ->paginate($perPage);
    }

    private function resolveSubjectType(string $type): string
    {
        return str_contains($type, '\\') ? $type : self::MODEL_NAMESPACE.$type;
    }
}
