<?php

namespace App\Models;

use App\Enums\SlaStatus;
use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'source',
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

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }

    /**
     * Resolution SLA against the linked agreement, weighted by severity.
     * Requires `supportAgreement` to be loaded; returns a "none" status otherwise.
     *
     * @return array{status: SlaStatus, due_at: ?Carbon, hours_remaining: ?float}
     */
    public function slaSnapshot(): array
    {
        $hours = $this->relationLoaded('supportAgreement') ? $this->supportAgreement?->response_sla_hours : null;

        if (! $hours || $this->opened_at === null) {
            return ['status' => SlaStatus::None, 'due_at' => null, 'hours_remaining' => null];
        }

        $factor = $this->severity instanceof SupportSeverity ? $this->severity->slaFactor() : 1.0;
        $dueAt = $this->opened_at->copy()->addMinutes((int) round($hours * $factor * 60));

        if (in_array($this->status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)) {
            $resolvedAt = $this->resolved_at ?? Carbon::now();

            return [
                'status' => $resolvedAt->lessThanOrEqualTo($dueAt) ? SlaStatus::Met : SlaStatus::Breached,
                'due_at' => $dueAt,
                'hours_remaining' => null,
            ];
        }

        $minutesRemaining = ($dueAt->getTimestamp() - Carbon::now()->getTimestamp()) / 60;
        $windowMinutes = $hours * $factor * 60;

        $status = match (true) {
            $minutesRemaining < 0 => SlaStatus::Breached,
            $minutesRemaining <= $windowMinutes * 0.25 => SlaStatus::AtRisk,
            default => SlaStatus::OnTrack,
        };

        return ['status' => $status, 'due_at' => $dueAt, 'hours_remaining' => round($minutesRemaining / 60, 1)];
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
