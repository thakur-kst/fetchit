<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\GoogleOAuthService;
use Modules\Auth\Services\JwtTokenService;
use Modules\DBCore\Models\Core\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

/**
 * Authentication Controller
 * 
 * Handles Google OAuth authentication and JWT token management.
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
     * POST /auth/google/verify
     */
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
                    'user' => [
                        'id' => $user->uuid,
                        'email' => $user->email,
                        'name' => $user->name,
                        'picture' => $user->picture,
                        'emailVerified' => $user->email_verified_at !== null,
                    ],
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
     * POST /auth/refresh
     */
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
     * GET /auth/me
     */
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
                'data' => [
                    'id' => $user->uuid,
                    'email' => $user->email,
                    'name' => $user->name,
                    'picture' => $user->picture,
                    'emailVerified' => $user->email_verified_at !== null,
                    'createdAt' => $user->created_at->toIso8601String(),
                ],
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
     * POST /auth/logout
     */
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
