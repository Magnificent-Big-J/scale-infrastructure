<?php

namespace App\Contracts;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ClientServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $tier = null): LengthAwarePaginator;

    public function create(array $data): Client;

    public function update(Client $client, array $data): Client;

    public function archive(Client $client): void;

    public function createContact(Client $client, array $data): Contact;

    public function updateContact(Contact $contact, array $data): Contact;

    public function archiveContact(Contact $contact): void;
}
