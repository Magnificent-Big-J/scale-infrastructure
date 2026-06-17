<?php

namespace App\Models;

use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'deployment_id',
        'support_agreement_id',
        'assigned_user_id',
        'reference',
        'subject',
        'category',
        'severity',
        'status',
        'hours_logged',
        'opened_at',
        'resolved_at',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'severity' => SupportSeverity::class,
            'status' => SupportTicketStatus::class,
            'hours_logged' => 'decimal:2',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function supportAgreement(): BelongsTo
    {
        return $this->belongsTo(SupportAgreement::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('subject', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }
}
