<?php

namespace Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DBCore\Models\Core\Country;
use Modules\Shared\Http\Resources\Api\V1\CountryResource;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

/**
 * Country Controller
 *
 * Handles API requests for countries (master data).
 *
 * @package Modules\Shared\Http\Controllers\Api\V1
 */
class CountryController extends Controller
{
    public function __construct(
        private CacheService $cacheService
    ) {
    }

    /**
     * Get all countries
     *
     * Returns a list of all countries without pagination.
     * Results are cached for improved performance.
     *
     * @operationId getCountries
     * @tags Master Data
     * @response 200 {"success": true, "data": [{"id": 1, "name": "India", "country_code": "IN", ...}]}
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cacheKey = CacheKeyGenerator::list('shared', 'countries');

        $countries = $this->cacheService->remember($cacheKey, function () {
            return Country::orderBy('name')->get();
        });

        return response()->json([
            'success' => true,
            'data' => CountryResource::collection($countries),
        ]);
    }
}

