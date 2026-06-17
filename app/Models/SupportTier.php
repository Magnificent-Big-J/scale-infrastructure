<?php

namespace App\Models;

use App\Enums\CatalogueStatus;
use App\Models\Concerns\HasCatalogueFilters;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTier extends Model
{
    use HasCatalogueFilters;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'monthly_fee',
        'included_hours',
        'response_sla_hours',
        'service_review',
        'best_for',
        'currency',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'included_hours' => 'integer',
            'response_sla_hours' => 'integer',
            'status' => CatalogueStatus::class,
            'sort_order' => 'integer',
        ];
    }
}
