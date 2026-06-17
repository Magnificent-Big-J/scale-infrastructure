<?php

namespace App\Models;

use App\Enums\MonitoringCheckStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonitoringCheck extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'deployment_id',
        'name',
        'check_type',
        'target',
        'status',
        'last_checked_at',
        'last_success_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => MonitoringCheckStatus::class,
            'last_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
