<?php

namespace App\Models;

use App\Enums\SupportAgreementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportAgreement extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'support_tier_id',
        'code',
        'name',
        'monthly_fee',
        'included_hours',
        'response_sla_hours',
        'starts_on',
        'ends_on',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'included_hours' => 'integer',
            'response_sla_hours' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => SupportAgreementStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function supportTier(): BelongsTo
    {
        return $this->belongsTo(SupportTier::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
