<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'contract_id',
        'number',
        'status',
        'amount',
        'amount_paid',
        'issued_on',
        'due_on',
        'external_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'issued_on' => 'date',
            'due_on' => 'date',
            'status' => InvoiceStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    protected function outstanding(): Attribute
    {
        return Attribute::get(fn () => number_format(max(0, (float) $this->amount - (float) $this->amount_paid), 2, '.', ''));
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_on !== null
            && $this->due_on->isPast()
            && (float) $this->outstanding > 0;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('number', 'like', "%{$term}%")
                    ->orWhere('external_reference', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', now())
            ->whereColumn('amount_paid', '<', 'amount');
    }
}
