<?php

namespace App\Services;

use App\Contracts\SlaServiceInterface;
use App\Models\SupportTicket;
use Illuminate\Support\Collection;

class SlaService implements SlaServiceInterface
{
    public function overview(): array
    {
        $tickets = SupportTicket::query()
            ->with(['client', 'supportAgreement', 'assignedUser', 'deployment'])
            ->whereNotNull('support_agreement_id')
            ->get();

        return [
            'tickets' => $this->orderByUrgency($tickets),
            'summary' => $this->summarise($tickets),
        ];
    }

    /**
     * @param  Collection<int, SupportTicket>  $tickets
     * @return array<string, int>
     */
    private function summarise(Collection $tickets): array
    {
        $summary = ['breached' => 0, 'at_risk' => 0, 'on_track' => 0, 'met' => 0];

        $tickets->each(function (SupportTicket $ticket) use (&$summary) {
            $key = $ticket->slaSnapshot()['status']->value;

            if (array_key_exists($key, $summary)) {
                $summary[$key]++;
            }
        });

        return $summary;
    }

    /**
     * @param  Collection<int, SupportTicket>  $tickets
     * @return Collection<int, SupportTicket>
     */
    private function orderByUrgency(Collection $tickets): Collection
    {
        return $tickets
            ->sort(function (SupportTicket $a, SupportTicket $b) {
                $slaA = $a->slaSnapshot();
                $slaB = $b->slaSnapshot();

                return [$slaA['status']->rank(), $slaA['hours_remaining'] ?? PHP_INT_MAX]
                    <=> [$slaB['status']->rank(), $slaB['hours_remaining'] ?? PHP_INT_MAX];
            })
            ->values();
    }
}
