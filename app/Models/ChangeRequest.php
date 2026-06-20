<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRisk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeRequest extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'deployment_id',
        'client_id',
        'reference',
        'title',
        'description',
        'risk',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'scheduled_for',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'risk' => ChangeRisk::class,
            'status' => ChangeRequestStatus::class,
            'approved_at' => 'datetime',
            'scheduled_for' => 'date',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === ChangeRequestStatus::Approved;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
