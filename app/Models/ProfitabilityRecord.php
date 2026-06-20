<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfitabilityRecord extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'period',
        'revenue',
        'hosting_cost',
        'labour_cost',
        'monitoring_cost',
        'other_cost',
        'profit',
        'margin',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'revenue' => 'decimal:2',
            'hosting_cost' => 'decimal:2',
            'labour_cost' => 'decimal:2',
            'monitoring_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'profit' => 'decimal:2',
            'margin' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function totalCost(): float
    {
        return (float) $this->hosting_cost + (float) $this->labour_cost + (float) $this->monitoring_cost + (float) $this->other_cost;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('period', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
