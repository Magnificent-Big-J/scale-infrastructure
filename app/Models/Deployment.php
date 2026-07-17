<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Deployment extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'product_id',
        'package_id',
        'name',
        'environment',
        'domain',
        'app_url',
        'current_version',
        'go_live_date',
        'status',
        'intake_token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeploymentStatus::class,
            'go_live_date' => 'date',
        ];
    }

    public function regenerateIntakeToken(): string
    {
        $this->forceFill(['intake_token' => 'dit_'.Str::random(40)])->save();

        return $this->intake_token;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function infrastructureAssets(): HasMany
    {
        return $this->hasMany(InfrastructureAsset::class);
    }

    public function monitoringChecks(): HasMany
    {
        return $this->hasMany(MonitoringCheck::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function automationRuns(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('domain', 'like', "%{$term}%")
                    ->orWhere('app_url', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $query) => $query->where('status', $status));
    }

    public function scopeWithEnvironment(Builder $query, ?string $environment): Builder
    {
        return $query->when($environment, fn (Builder $query) => $query->where('environment', $environment));
    }
}
