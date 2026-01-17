<?php

namespace Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DBCore\Models\Core\City;
use Modules\Shared\Http\Requests\Api\V1\GetCitiesRequest;
use Modules\Shared\Http\Resources\Api\V1\CityResource;

/**
 * City Controller
 *
 * Handles API requests for cities (master data).
 *
 * @package Modules\Shared\Http\Controllers\Api\V1
 */
class CityController extends Controller
{
    /**
     * Get cities by country code with search
     *
     * Returns a paginated list of cities filtered by country_code (ISO2 format, mandatory)
     * and filtered by city name search term (mandatory, minimum 1 character).
     * Results are not cached due to search functionality.
     *
     * @operationId getCities
     * @tags Master Data
     * @param GetCitiesRequest $request
     * @response 200 {"data": [{"id": 1, "name": "Mumbai", "country_code": "IN", "code": "MUM", ...}], "meta": {...}}
     * @return JsonResponse
     */
    public function index(GetCitiesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $countryCode = $validated['country_code'];
        $search = $validated['search'];
        $perPage = $validated['per_page'] ?? 10;

        $cities = City::where('country_code', $countryCode)
            ->where('name', 'ILIKE', "%{$search}%")
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->except('page'));

        return response()->json([
            'success' => true,
            'data' => CityResource::collection($cities),
            'meta' => [
                'current_page' => $cities->currentPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
                'last_page' => $cities->lastPage(),
                'from' => $cities->firstItem(),
                'to' => $cities->lastItem(),
            ],
        ]);
    }
}

