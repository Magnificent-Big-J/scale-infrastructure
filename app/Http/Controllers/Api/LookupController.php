<?php

namespace App\Http\Controllers\Api;

use App\Contracts\LookupOptionServiceInterface;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Models\LookupOption;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function __construct(private readonly LookupOptionServiceInterface $service) {}

    /**
     * Active options for a single managed list, in select-friendly shape.
     * Available to any authenticated user so forms can populate their dropdowns.
     */
    public function show(string $type): JsonResponse
    {
        $lookupType = LookupType::tryFrom($type);

        abort_if($lookupType === null, 404);

        $data = $this->service->optionsFor($lookupType)
            ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
            ->all();

        return response()->json(['data' => $data]);
    }
}
