<?php

namespace App\Models;

use App\Enums\ReleaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Release extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'deployment_id',
        'change_request_id',
        'version',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'deployed_by',
        'deployed_at',
        'rolled_back_at',
        'rollback_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReleaseStatus::class,
            'approved_at' => 'datetime',
            'deployed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deployer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deployed_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('version', 'like', "%{$term}%")
                    ->orWhereHas('deployment', fn (Builder $deploymentQuery) => $deploymentQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
