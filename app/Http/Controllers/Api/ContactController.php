<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ClientServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreContactRequest;
use App\Http\Requests\Clients\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly ClientServiceInterface $service
    ) {}

    public function store(StoreContactRequest $request, Client $client): JsonResponse
    {
        $contact = $this->service->createContact($client, $request->validated());

        return response()->json(new ContactResource($contact), 201);
    }

    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        return new ContactResource($this->service->updateContact($contact, $request->validated()));
    }

    public function destroy(Contact $contact): Response
    {
        $this->service->archiveContact($contact);

        return response()->noContent();
    }
}
