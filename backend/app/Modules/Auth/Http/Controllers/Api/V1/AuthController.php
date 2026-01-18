<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\GoogleOAuthService;
use Modules\Auth\Services\JwtTokenService;
use Illuminate\Support\Facades\Log;
use Dedoc\Scramble\Attributes\Response;
/**
 * Authentication Controller
 *
 * Handles Google OAuth authentication and JWT token management.
 *
 * @tags Auth
 */
class AuthController extends Controller
{
    public function __construct(
        private GoogleOAuthService $googleOAuthService,
        private JwtTokenService $jwtTokenService
    ) {
    }

    /**
     * Verify Google ID token and generate JWT tokens
     *
     * Exchanges a Google ID token for user info, finds or creates the user, and returns JWT access and refresh tokens.
     *
     * @operationId authVerifyGoogle
     * @tags Auth
     * @response 401 {
     *   "success": false,
     *   "error": "Invalid or expired Google token"
     * }
     * @response 500 {
     *   "success": false,
     *   "error": "Authentication failed"
     * }
     */
    #[Response(200, 'Authentication successful', type: 'array{user: UserResource, tokens: array{accessToken: string, refreshToken: string, expiresIn: int}}')]
    public function verifyGoogleToken(Request $request): JsonResponse
    {
        $request->validate([
            'idToken' => 'required|string',
            'deviceName' => 'nullable|string|max:255',
            'deviceId' => 'nullable|string|max:255',
        ]);

        try {
            // Verify Google ID token
            $googleData = $this->googleOAuthService->verifyIdToken($request->idToken);
            
            if (!$googleData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired Google token',
                ], 401);
            }

            // Find or create user
            $user = $this->googleOAuthService->findOrCreateUser($googleData);

            // Generate JWT tokens
            $tokens = $this->jwtTokenService->generateTokens(
                $user,
                $request->deviceName,
                $request->deviceId
            );

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'data' => [
                    'user' => new UserResource($user),
                    'tokens' => $tokens,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error verifying Google token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Authentication failed',
            ], 500);
        }
    }

    /**
     * Refresh access token
     *
     * Exchanges a valid refresh token for new access and refresh tokens.
     *
     * @operationId authRefresh
     * @tags Auth
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiYXVkIjoiaHR0cDovL2xvY2FsaG9zdCIsImlhdCI6MTcwNDAwMDAwMCwiZXhwIjoxNzA0MDAzNjAwfQ.example",
     *     "refreshToken": "def50200a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
     *     "expiresIn": 3600
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "error": "Invalid or expired refresh token"
     * }
     * @response 500 {
     *   "success": false,
     *   "error": "Token refresh failed"
     * }
     */
    #[Response(200, 'Token refreshed successfully', type: 'array{accessToken: string, refreshToken: string, expiresIn: int}')]
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refreshToken' => 'required|string',
        ]);

        try {
            $tokens = $this->jwtTokenService->refreshAccessToken($request->refreshToken);

            if (!$tokens) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired refresh token',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $tokens,
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Token refresh failed',
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     *
     * Returns the profile of the authenticated user. Requires Bearer token.
     *
     * @operationId authMe
     * @tags Auth
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": "550e8400-e29b-41d4-a716-446655440000",
     *     "email": "user@example.com",
     *     "name": "John Doe",
     *     "picture": "https://lh3.googleusercontent.com/a/default-user",
     *     "emailVerified": true,
     *     "createdAt": "2024-01-01T12:00:00Z"
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "error": "Unauthenticated"
     * }
     * @response 500 {
     *   "success": false,
     *   "error": "Failed to get user"
     * }
     */
    #[Response(200, 'User profile retrieved successfully', type: 'UserResource')]
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->jwtTokenService->getAuthenticatedUser();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting current user', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get user',
            ], 500);
        }
    }

    /**
     * Logout user
     *
     * Revokes the refresh token so it cannot be used again. Requires Bearer token and refreshToken in body.
     *
     * @operationId authLogout
     * @tags Auth
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully logged out"
     * }
     * @response 400 {
     *   "success": false,
     *   "error": "Invalid refresh token"
     * }
     * @response 500 {
     *   "success": false,
     *   "error": "Logout failed"
     * }
     */
    #[Response(200, 'Successfully logged out', type: 'array{success: bool, message: string}')]
    public function logout(Request $request): JsonResponse
    {
        $request->validate([
            'refreshToken' => 'required|string',
        ]);

        try {
            $revoked = $this->jwtTokenService->revokeRefreshToken($request->refreshToken);

            if (!$revoked) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid refresh token',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            Log::error('Error during logout', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Logout failed',
            ], 500);
        }
    }
}
