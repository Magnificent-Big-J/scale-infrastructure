<?php

namespace App\Services;

use App\Contracts\ProfitabilityServiceInterface;
use App\Models\ProfitabilityRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProfitabilityService implements ProfitabilityServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $period = null, ?string $clientId = null): LengthAwarePaginator
    {
        return ProfitabilityRecord::query()
            ->with('client')
            ->search($search)
            ->when($period, fn ($query) => $query->where('period', $period))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByDesc('period')
            ->orderByDesc('profit')
            ->paginate($perPage);
    }

    public function create(array $data): ProfitabilityRecord
    {
        return DB::transaction(function () use ($data) {
            $record = ProfitabilityRecord::query()->create($this->withDerived($data));

            $this->log($record, 'created', 'Created profitability record');

            return $record->load('client');
        });
    }

    public function update(ProfitabilityRecord $record, array $data): ProfitabilityRecord
    {
        return DB::transaction(function () use ($record, $data) {
            $record->fill($data);
            $record->fill($this->derive($record));
            $record->save();

            $this->log($record, 'updated', 'Updated profitability record', ['changes' => array_keys($record->getChanges())]);

            return $record->refresh()->load('client');
        });
    }

    public function summary(): array
    {
        $revenue = (float) ProfitabilityRecord::query()->sum('revenue');
        $cost = (float) ProfitabilityRecord::query()
            ->selectRaw('coalesce(sum(hosting_cost + labour_cost + monitoring_cost + other_cost), 0) as total')
            ->value('total');
        $profit = (float) ProfitabilityRecord::query()->sum('profit');

        return [
            'revenue_total' => round($revenue, 2),
            'cost_total' => round($cost, 2),
            'profit_total' => round($profit, 2),
            'margin_avg' => $revenue > 0 ? round($profit / $revenue * 100, 2) : 0.0,
            'records' => ProfitabilityRecord::query()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withDerived(array $data): array
    {
        $revenue = (float) ($data['revenue'] ?? 0);
        $cost = (float) ($data['hosting_cost'] ?? 0) + (float) ($data['labour_cost'] ?? 0)
            + (float) ($data['monitoring_cost'] ?? 0) + (float) ($data['other_cost'] ?? 0);
        $profit = round($revenue - $cost, 2);

        return [
            ...$data,
            'profit' => $profit,
            'margin' => $revenue > 0 ? round($profit / $revenue * 100, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function derive(ProfitabilityRecord $record): array
    {
        $revenue = (float) $record->revenue;
        $profit = round($revenue - $record->totalCost(), 2);

        return [
            'profit' => $profit,
            'margin' => $revenue > 0 ? round($profit / $revenue * 100, 2) : 0.0,
        ];
    }

    private function log(ProfitabilityRecord $record, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('profitability')
            ->performedOn($record)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
