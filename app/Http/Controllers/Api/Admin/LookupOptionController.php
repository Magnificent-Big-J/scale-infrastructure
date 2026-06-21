<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\LookupOptionServiceInterface;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lookup\StoreLookupOptionRequest;
use App\Http\Requests\Lookup\UpdateLookupOptionRequest;
use App\Http\Resources\LookupOptionResource;
use App\Models\LookupOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LookupOptionController extends Controller
{
    public function __construct(private readonly LookupOptionServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $type = $request->string('type')->toString() ?: null;
        $search = $request->string('search')->toString() ?: null;

        $options = $this->service->paginate($perPage, $type, $search);

        return response()->json([
            'data' => LookupOptionResource::collection($options->items()),
            'meta' => [
                'current_page' => $options->currentPage(),
                'last_page' => $options->lastPage(),
                'per_page' => $options->perPage(),
                'total' => $options->total(),
            ],
            'options' => [
                'types' => LookupType::options(),
            ],
        ]);
    }

    public function store(StoreLookupOptionRequest $request): JsonResponse
    {
        $option = $this->service->create($request->validated());

        return response()->json(new LookupOptionResource($option), 201);
    }

    public function update(UpdateLookupOptionRequest $request, LookupOption $lookupOption): LookupOptionResource
    {
        return new LookupOptionResource($this->service->update($lookupOption, $request->validated()));
    }

    public function destroy(LookupOption $lookupOption): Response
    {
        $this->service->delete($lookupOption);

        return response()->noContent();
    }
}
