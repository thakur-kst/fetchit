<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\LogoutRequest;
use Modules\Auth\Services\LoginService;

/**
 * Login Controller
 *
 * Handles user authentication.
 * Note: FetchIt uses Google OAuth for authentication. This controller is kept for backward compatibility.
 *
 * @tags Auth
 */
class LoginController extends Controller
{
    public function __construct(
        private LoginService $loginService
    ) {
    }

    /**
     * Login user
     *
     * @tags Auth
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->loginService->login(
                $validated['email'],
                $validated['password']
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Refresh access token
     *
     * Uses refresh token to get a new access token without requiring user to login again.
     *
     * @tags Auth
     * @param RefreshTokenRequest $request
     * @return JsonResponse
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->loginService->refresh($validated['refresh_token']);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout user
     *
     * Requires Bearer token authentication via auth.middleware.
     *
     * @tags Auth
     * @param LogoutRequest $request
     * @return JsonResponse
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        // Get token from Bearer header (required for authentication)
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Bearer token is required',
            ], 401);
        }

        try {
            $success = $this->loginService->logout($token);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Logout successful' : 'Logout failed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
