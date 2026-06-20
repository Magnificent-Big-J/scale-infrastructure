<?php

namespace App\Services;

use App\Contracts\ClientServiceInterface;
use App\Enums\SupportAgreementStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Deployment;
use App\Models\Invoice;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClientService implements ClientServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $tier = null): LengthAwarePaginator
    {
        return Client::query()
            ->with(['package.product', 'owner', 'primaryContact'])
            ->withCount('contacts')
            ->search($search)
            ->withStatus($status)
            ->withTier($tier)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function summary(Client $client): array
    {
        $deployments = Deployment::query()->where('client_id', $client->id)->count();

        $activeAgreements = SupportAgreement::query()
            ->where('client_id', $client->id)
            ->where('status', SupportAgreementStatus::Active->value)
            ->count();

        $openTickets = SupportTicket::query()
            ->where('client_id', $client->id)
            ->whereNotIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value])
            ->count();

        $contracts = Contract::query()->where('client_id', $client->id)->count();

        $outstanding = (float) Invoice::query()
            ->where('client_id', $client->id)
            ->open()
            ->selectRaw('coalesce(sum(amount - amount_paid), 0) as total')
            ->value('total');

        $overdue = Invoice::query()->where('client_id', $client->id)->overdue()->count();

        return [
            'deployments_count' => $deployments,
            'active_agreements' => $activeAgreements,
            'open_tickets' => $openTickets,
            'contracts_count' => $contracts,
            'outstanding_total' => round($outstanding, 2),
            'overdue_count' => $overdue,
        ];
    }

    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $primaryContact = Arr::pull($data, 'primary_contact');
            $client = Client::query()->create($data);

            if (is_array($primaryContact) && ($primaryContact['name'] ?? null)) {
                $this->createContact($client, [...$primaryContact, 'is_primary' => true]);
            }

            $this->log($client, 'created', 'Created client');

            return $client->refresh()->load(['package.product', 'owner', 'primaryContact'])->loadCount('contacts');
        });
    }

    public function update(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $primaryContact = Arr::pull($data, 'primary_contact');

            $client->fill($data);
            $client->save();

            if (is_array($primaryContact) && ($primaryContact['name'] ?? null)) {
                $contact = $client->primaryContact()->first();

                if ($contact) {
                    $this->updateContact($contact, [...$primaryContact, 'is_primary' => true]);
                } else {
                    $this->createContact($client, [...$primaryContact, 'is_primary' => true]);
                }
            }

            $this->log($client, 'updated', 'Updated client', ['changes' => array_keys($client->getChanges())]);

            return $client->refresh()->load(['package.product', 'owner', 'primaryContact'])->loadCount('contacts');
        });
    }

    public function archive(Client $client): void
    {
        DB::transaction(function () use ($client) {
            $client->delete();

            $this->log($client, 'archived', 'Archived client');
        });
    }

    public function createContact(Client $client, array $data): Contact
    {
        return DB::transaction(function () use ($client, $data) {
            if ($data['is_primary'] ?? false) {
                $this->clearPrimaryContact($client);
            }

            $contact = $client->contacts()->create($data);

            $this->log($client, 'contact_created', 'Created client contact', ['contact_id' => $contact->id]);

            return $contact->refresh()->load('client');
        });
    }

    public function updateContact(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            if ($data['is_primary'] ?? false) {
                $this->clearPrimaryContact($contact->client, $contact);
            }

            $contact->fill($data);
            $contact->save();

            $this->log($contact->client, 'contact_updated', 'Updated client contact', ['contact_id' => $contact->id]);

            return $contact->refresh()->load('client');
        });
    }

    public function archiveContact(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            $client = $contact->client;
            $contact->delete();

            $this->log($client, 'contact_archived', 'Archived client contact', ['contact_id' => $contact->id]);
        });
    }

    private function clearPrimaryContact(Client $client, ?Contact $except = null): void
    {
        $client->contacts()
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->update(['is_primary' => false]);
    }

    private function log(Client $client, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('clients')
            ->performedOn($client)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
