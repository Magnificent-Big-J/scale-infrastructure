<?php

namespace App\Models;

use App\Enums\AutomationRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'provisioning_template_id',
        'deployment_id',
        'client_id',
        'change_request_id',
        'reference',
        'status',
        'started_at',
        'finished_at',
        'output_summary',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AutomationRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function provisioningTemplate(): BelongsTo
    {
        return $this->belongsTo(ProvisioningTemplate::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('reference', 'like', "%{$term}%")
                    ->orWhereHas('provisioningTemplate', fn (Builder $tpl) => $tpl->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
