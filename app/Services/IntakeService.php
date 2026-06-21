<?php

namespace App\Services;

use App\Contracts\IntakeServiceInterface;
use App\Contracts\SupportOperationsServiceInterface;
use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\Deployment;
use App\Models\SupportTicket;
use Illuminate\Support\Str;

class IntakeService implements IntakeServiceInterface
{
    public function __construct(private readonly SupportOperationsServiceInterface $tickets) {}

    public function createTicket(Deployment $deployment, array $data): SupportTicket
    {
        return $this->tickets->createTicket([
            'client_id' => $deployment->client_id,
            'deployment_id' => $deployment->id,
            'reference' => $this->uniqueReference(),
            'source' => 'intake',
            'subject' => $data['subject'],
            'category' => $data['category'] ?? null,
            'severity' => $data['severity'] ?? SupportSeverity::Low->value,
            'status' => SupportTicketStatus::Open->value,
            'hours_logged' => 0,
            'opened_at' => now(),
            'summary' => $data['summary'] ?? null,
        ]);
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'TCK-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (SupportTicket::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
