<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModuleDemoRecordResource;
use App\Models\ModuleDemoRecord;
use Illuminate\Http\JsonResponse;

class ModuleDemoRecordController extends Controller
{
    public function index(string $pageKey): JsonResponse
    {
        $records = ModuleDemoRecord::query()
            ->where('page_key', $pageKey)
            ->orderBy('sort_order')
            ->orderBy('headline')
            ->get();

        abort_if($records->isEmpty(), 404);

        $permission = $records->first()->permission;

        abort_unless(auth()->user()?->can($permission), 403);

        return response()->json([
            'data' => ModuleDemoRecordResource::collection($records),
            'summary' => [
                'total' => $records->count(),
                'statuses' => $records->countBy('status')->all(),
                'permission' => $permission,
            ],
        ]);
    }
}
