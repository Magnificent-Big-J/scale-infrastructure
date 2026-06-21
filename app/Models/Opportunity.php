<?php

namespace App\Models;

use App\Enums\OpportunityStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'opportunities';

    protected $fillable = [
        'client_id',
        'prospect_name',
        'owner_id',
        'contract_id',
        'title',
        'description',
        'stage',
        'value',
        'probability',
        'source',
        'expected_close_date',
        'won_at',
        'lost_at',
        'lost_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('prospect_name', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
