<?php

namespace Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DBCore\Models\Core\State;
use Modules\Shared\Http\Requests\Api\V1\GetStatesRequest;
use Modules\Shared\Http\Resources\Api\V1\StateResource;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

/**
 * State Controller
 *
 * Handles API requests for states (master data).
 *
 * @package Modules\Shared\Http\Controllers\Api\V1
 */
class StateController extends Controller
{
    public function __construct(
        private CacheService $cacheService
    ) {
    }

    /**
     * Get states by country code
     *
     * Returns a list of states filtered by country_code (ISO2 format).
     * Results are cached for improved performance.
     *
     * @operationId getStates
     * @tags Master Data
     * @param GetStatesRequest $request
     * @response 200 {"success": true, "data": [{"id": 1, "name": "Maharashtra", "country_code": "IN", "code": "MH", ...}]}
     * @return JsonResponse
     */
    public function index(GetStatesRequest $request): JsonResponse
    {
        $countryCode = $request->validated()['country_code'];

        $cacheKey = CacheKeyGenerator::list('shared', 'states', ['country_code' => $countryCode]);

        $states = $this->cacheService->remember($cacheKey, function () use ($countryCode) {
            return State::where('country_code', $countryCode)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => StateResource::collection($states),
        ]);
    }
}

