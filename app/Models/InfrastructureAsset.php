<?php

namespace App\Models;

use App\Enums\InfrastructureAssetType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InfrastructureAsset extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'deployment_id',
        'name',
        'type',
        'provider',
        'region',
        'size',
        'monthly_cost',
        'currency',
        'public_ip',
        'private_ip',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => InfrastructureAssetType::class,
            'monthly_cost' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
