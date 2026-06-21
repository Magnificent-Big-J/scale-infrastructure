<?php

namespace App\Services;

use App\Contracts\CommercialOperationsServiceInterface;
use App\Contracts\OpportunityServiceInterface;
use App\Enums\ContractStatus;
use App\Enums\OpportunityStage;
use App\Models\Opportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OpportunityService implements OpportunityServiceInterface
{
    public function __construct(private readonly CommercialOperationsServiceInterface $contracts) {}

    public function paginate(int $perPage = 15, ?string $search = null, ?string $stage = null, ?string $clientId = null, ?int $ownerId = null): LengthAwarePaginator
    {
        return Opportunity::query()
            ->with(['client', 'owner', 'contract'])
            ->search($search)
            ->when($stage, fn ($query) => $query->where('stage', $stage))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->orderByDesc('value')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Opportunity
    {
        return DB::transaction(function () use ($data) {
            $opportunity = Opportunity::query()->create($this->stamp($data));

            $this->log($opportunity, 'created', 'Created opportunity');

            return $opportunity->load(['client', 'owner', 'contract']);
        });
    }

    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $data) {
            $opportunity->fill($this->stamp($data, $opportunity));
            $opportunity->save();

            $this->log($opportunity, 'updated', 'Updated opportunity', ['changes' => array_keys($opportunity->getChanges())]);

            return $opportunity->refresh()->load(['client', 'owner', 'contract']);
        });
    }

    public function delete(Opportunity $opportunity): void
    {
        DB::transaction(function () use ($opportunity) {
            $opportunity->delete();

            $this->log($opportunity, 'deleted', 'Deleted opportunity');
        });
    }

    public function summary(): array
    {
        $open = Opportunity::query()->whereNotIn('stage', [OpportunityStage::Won->value, OpportunityStage::Lost->value]);
        $won = Opportunity::query()->where('stage', OpportunityStage::Won->value);

        $wonCount = (clone $won)->count();
        $lostCount = Opportunity::query()->where('stage', OpportunityStage::Lost->value)->count();
        $decided = $wonCount + $lostCount;

        return [
            'open_count' => (clone $open)->count(),
            'open_value' => round((float) (clone $open)->sum('value'), 2),
            'won_count' => $wonCount,
            'won_value' => round((float) (clone $won)->sum('value'), 2),
            'win_rate' => $decided > 0 ? round($wonCount / $decided * 100, 1) : 0.0,
        ];
    }

    public function pipeline(): array
    {
        $aggregates = Opportunity::query()
            ->selectRaw('stage, count(*) as records, coalesce(sum(value), 0) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        return array_map(function (OpportunityStage $stage) use ($aggregates) {
            $row = $aggregates->get($stage->value);

            return [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'count' => (int) ($row->records ?? 0),
                'value' => round((float) ($row->total ?? 0), 2),
            ];
        }, OpportunityStage::cases());
    }

    public function markWon(Opportunity $opportunity): Opportunity
    {
        return DB::transaction(function () use ($opportunity) {
            $opportunity->fill($this->stamp(['stage' => OpportunityStage::Won->value], $opportunity));

            // A contract needs a client; prospect-only wins are marked won without one.
            if ($opportunity->client_id !== null && $opportunity->contract_id === null) {
                $contract = $this->contracts->createContract([
                    'client_id' => $opportunity->client_id,
                    'code' => 'CON-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
                    'name' => $opportunity->title,
                    'total_value' => $opportunity->value,
                    'status' => ContractStatus::Draft->value,
                    'notes' => 'Created from won opportunity.',
                ]);

                $opportunity->contract_id = $contract->id;
            }

            $opportunity->save();

            $this->log($opportunity, 'won', 'Marked opportunity won');

            return $opportunity->refresh()->load(['client', 'owner', 'contract']);
        });
    }

    /**
     * Stamp won_at / lost_at based on the resulting stage.
     */
    private function stamp(array $data, ?Opportunity $opportunity = null): array
    {
        $stage = $data['stage'] ?? $opportunity?->stage?->value;

        if ($stage === OpportunityStage::Won->value) {
            $data['won_at'] = $opportunity?->won_at ?? Carbon::now();
            $data['lost_at'] = null;
        } elseif ($stage === OpportunityStage::Lost->value) {
            $data['lost_at'] = $opportunity?->lost_at ?? Carbon::now();
            $data['won_at'] = null;
        } elseif ($stage !== null) {
            // Reopened — clear close timestamps.
            $data['won_at'] = null;
            $data['lost_at'] = null;
        }

        return $data;
    }

    private function log(Opportunity $opportunity, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('opportunities')
            ->performedOn($opportunity)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
