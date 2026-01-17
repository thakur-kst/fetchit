<?php

namespace Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DBCore\Models\Core\Postcode;
use Modules\Shared\Http\Requests\Api\V1\GetPostcodesRequest;
use Modules\Shared\Http\Resources\Api\V1\PostcodeResource;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

/**
 * Postcode Controller
 *
 * Handles API requests for postcodes (master data).
 *
 * @package Modules\Shared\Http\Controllers\Api\V1
 */
class PostcodeController extends Controller
{
    public function __construct(
        private CacheService $cacheService
    ) {
    }

    /**
     * Get postcodes by country code and postcode
     *
     * Returns a list of postcodes filtered by country_code (ISO2 format, mandatory)
     * and postcode (mandatory).
     * Results are cached for improved performance.
     *
     * @operationId getPostcodes
     * @tags Master Data
     * @param GetPostcodesRequest $request
     * @response 200 {"success": true, "data": [{"id": 1, "country_code": "IN, "state_code": "MH", "city_code": "MUM", "postcode": "400001", "locality": "Mumbai", ...}]}
     * @return JsonResponse
     */
    public function index(GetPostcodesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $countryCode = $validated['country_code'];
        $postcode = $validated['postcode'];

        $cacheKey = CacheKeyGenerator::list('shared', 'postcodes', [
            'country_code' => $countryCode,
            'postcode' => $postcode,
        ]);

        $postcodes = $this->cacheService->remember($cacheKey, function () use ($countryCode, $postcode) {
            return Postcode::where('country_code', $countryCode)
                ->where('postcode', $postcode)
                ->with('cit')
                ->orderBy('locality')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => PostcodeResource::collection($postcodes),
        ]);
    }
}

