<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Auth\Services\LaravelJwtService;
use Modules\DBCore\Models\Core\User;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication Middleware
 *
 * Handles JWT authentication for FetchIt API.
 * Uses Laravel JWT (tymon/jwt-auth) for token validation.
 */
class AuthMiddleware
{
    private ?LaravelJwtService $jwtService = null;

    public function __construct(?LaravelJwtService $jwtService = null)
    {
        $this->jwtService = $jwtService ?? app(LaravelJwtService::class);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated. Bearer token required.',
            ], 401);
        }

        // Check if token is blacklisted (logged out)
        if ($this->jwtService->isTokenBlacklisted($token)) {
            return response()->json([
                'message' => 'Token has been invalidated. Please login again.',
            ], 401);
        }

        // Handle JWT authentication
        $decoded = $this->jwtService->validateToken($token);

        if (!$decoded) {
            return response()->json([
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // JWT token is valid
        $userId = $decoded['sub'] ?? null;

        if (!$userId) {
            return response()->json([
                'message' => 'Invalid token format',
            ], 401);
        }

        // Find and set user
        $user = User::where('uuid', $userId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 401);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'User account is disabled. Please contact support.',
            ], 403);
        }

        // Set authenticated user on JWT guard
        Auth::guard('jwt')->setUser($user);

        // Set the request user resolver to ensure $request->user() works
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Change the default guard to 'jwt' for this request
        Auth::shouldUse('jwt');

        return $next($request);
    }
}
