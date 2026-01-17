<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\SetupPasswordRequest;
use Modules\Auth\Services\PasswordSetupService;

/**
 * Password Setup Controller
 *
 * Handles password setup for new customers.
 *
 * @tags Auth
 */
class PasswordSetupController extends Controller
{
    public function __construct(
        private PasswordSetupService $passwordSetupService
    ) {
    }

    /**
     * Setup password using token
     *
     * Sets up a password for a new customer using a token received via email.
     * The token can be provided either in the request body or as a query parameter.
     *
     * @tags Auth
     * @param SetupPasswordRequest $request
     * @return JsonResponse
     */
    public function setupPassword(SetupPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Token can come from query string (email link) or request body
        $token = $request->query('token') ?? $validated['token'];

        try {
            $result = $this->passwordSetupService->setupPassword(
                $token,
                $validated['password'],
                $validated['confirm_password']
            );

            return response()->json([
                'success' => true,
                'message' => 'Password set successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

