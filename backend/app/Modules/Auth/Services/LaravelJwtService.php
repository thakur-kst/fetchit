<?php

namespace Modules\Auth\Services;

use Modules\DBCore\Models\Core\User;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Laravel JWT Authentication Service
 *
 * Handles authentication using Laravel's built-in password hashing and JWT tokens.
 */
class LaravelJwtService
{
    private string $secret;
    private string $algorithm;
    private int $ttl;
    private int $refreshTtl;
    private string $loginTokenSalt;
    private string $tokenHashAlgorithm;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
        $loginTokenSalt = config('auth_method.login_token_salt');
        $this->loginTokenSalt = $loginTokenSalt;
        $this->tokenHashAlgorithm = config('auth_method.token_hash_algorithm');
        // Use LOGIN_TOKEN_SALT as the secret (hashed for consistency)
        $this->secret = hash($this->tokenHashAlgorithm, $this->loginTokenSalt);
        $this->algorithm = config('auth_method.jwt.algorithm');
        $this->ttl = (int) config('auth_method.ttl'); // 24 hours in minutes (common config)
        $this->refreshTtl = (int) config('auth_method.refresh_ttl'); // 7 days in minutes (common config)
    }

    /**
     * Validate user credentials
     */
    public function validateUserCredentials(string $email, string $password): ?array
    {
        try {
            // Find user by email (case-insensitive)
            $user = $this->findUserByEmailCaseInsensitive($email);

            if (!$user) {
                Log::warning('User not found for login', ['email' => $email]);
                return null;
            }

            // Verify password
            if (!Hash::check($password, $user->password)) {
                Log::warning('Invalid password for user', ['email' => $email, 'user_id' => $user->id]);
                return null;
            }

            // Generate JWT token
            $token = $this->generateToken($user);
            $refreshToken = $this->generateRefreshToken($user);

            return [
                'id' => (string) $user->id,
                'username' => $user->email,
                'email' => $user->email,
                'token' => $token,
                'refresh_token' => $refreshToken,
            ];
        } catch (\Exception $e) {
            Log::error('Exception validating credentials with Laravel JWT', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);

            return null;
        }
    }

    /**
     * Generate JWT access token
     */
    private function generateToken(User $user): string
    {
        $now = time();
        $exp = $now + ($this->ttl * 60); // Convert minutes to seconds

        $payload = [
            'iss' => config('app.url'), // Issuer
            'aud' => config('app.url'), // Audience
            'iat' => $now, // Issued at
            'exp' => $exp, // Expiration
            'sub' => (string) $user->id, // Subject (user ID)
            'email' => $user->email,
            'name' => $user->name,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate JWT refresh token
     */
    private function generateRefreshToken(User $user): string
    {
        $now = time();
        $exp = $now + ($this->refreshTtl * 60); // Convert minutes to seconds

        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'exp' => $exp,
            'sub' => (string) $user->id,
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Validate and decode JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            Log::warning('Invalid JWT token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken The refresh token
     * @return array|null New access token and refresh token, or null if invalid
     */
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            // Validate refresh token
            $decoded = $this->validateToken($refreshToken);

            if (!$decoded) {
                Log::warning('Invalid refresh token format');
                return null;
            }

            // Check if it's actually a refresh token
            if (($decoded['type'] ?? null) !== 'refresh') {
                Log::warning('Token is not a refresh token', ['type' => $decoded['type'] ?? 'none']);
                return null;
            }

            // Get user ID from token
            $userId = $decoded['sub'] ?? null;
            if (!$userId) {
                Log::warning('Refresh token missing user ID');
                return null;
            }

            // Find user
            $user = $this->userRepository->find($userId);
            if (!$user) {
                Log::warning('User not found for refresh token', ['user_id' => $userId]);
                return null;
            }

            // Generate new tokens
            $newAccessToken = $this->generateToken($user);
            $newRefreshToken = $this->generateRefreshToken($user);

            return [
                'id' => (string) $user->id,
                'username' => $user->email,
                'email' => $user->email,
                'token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
            ];
        } catch (\Exception $e) {
            Log::error('Exception refreshing token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Logout user (invalidate token)
     * 
     * Blacklists the token to prevent its use after logout.
     */
    public function logout(string $token): bool
    {
        try {
            // Validate token format (optional - ensures token is valid JWT)
            $decoded = $this->validateToken($token);

            if ($decoded) {
                // Calculate TTL from token expiration
                $ttl = 24 * 60 * 60; // Default 24 hours
                if (isset($decoded['exp'])) {
                    $remaining = max(0, $decoded['exp'] - time());
                    $ttl = max($remaining, 3600); // At least 1 hour
                }

                // Blacklist the token
                $this->blacklistToken($token, 'jwt', $ttl);
                return true;
            }

            // Invalid token format
            Log::warning('Invalid token format during logout attempt');
            return false;
        } catch (\Exception $e) {
            Log::error('Exception during logout', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a token is blacklisted (public method for middleware)
     * 
     * @param string $token The token to check
     * @return bool True if token is blacklisted
     */
    public function isTokenBlacklisted(string $token): bool
    {
        return $this->isTokenBlacklistedInternal($token, 'jwt');
    }

    /**
     * Enable user (no-op for Laravel JWT, status is managed in database)
     *
     * @param string $userId User ID (not used, but kept for interface compatibility)
     * @return bool
     */
    public function enableUser(string $userId): bool
    {
        // For Laravel JWT, user status is managed in the database
        // This method exists for interface compatibility
        return true;
    }

    /**
     * Disable user (no-op for Laravel JWT, status is managed in database)
     *
     * @param string $userId User ID (not used, but kept for interface compatibility)
     * @return bool
     */
    public function disableUser(string $userId): bool
    {
        // For Laravel JWT, user status is managed in the database
        // This method exists for interface compatibility
        return true;
    }

    /**
     * Logout all sessions (no-op for Laravel JWT, tokens are checked in middleware)
     *
     * @param string $userId User ID (not used, but kept for interface compatibility)
     * @return bool
     */
    public function logoutAllSessions(string $userId): bool
    {
        // For Laravel JWT, we can't invalidate all tokens at once
        // Instead, we check user status in the middleware
        // This method exists for interface compatibility
        return true;
    }

    /**
     * Find user by email (case-insensitive)
     * 
     * @param string $email The email to search for
     * @return User|null
     */
    private function findUserByEmailCaseInsensitive(string $email): ?User
    {
        // Try exact match first
        return $this->userRepository->findByEmailCaseInsensitive($email);
    }

    /**
     * Blacklist a token to prevent its use after logout
     * 
     * @param string $token The token to blacklist
     * @param string $prefix Cache key prefix
     * @param int|null $ttl Time to live in seconds
     * @return void
     */
    private function blacklistToken(string $token, string $prefix = 'jwt', ?int $ttl = null): void
    {
        try {
            $tokenHash = hash($this->tokenHashAlgorithm, $token);
            $ttl = $ttl ?? (24 * 60 * 60);
            Cache::put("blacklist:{$prefix}:{$tokenHash}", true, now()->addSeconds($ttl));
        } catch (\Exception $e) {
            Log::warning("Failed to blacklist token ({$prefix})", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if a token is blacklisted (internal method)
     * 
     * @param string $token The token to check
     * @param string $prefix Cache key prefix
     * @return bool True if token is blacklisted
     */
    private function isTokenBlacklistedInternal(string $token, string $prefix = 'jwt'): bool
    {
        try {
            $tokenHash = hash($this->tokenHashAlgorithm, $token);
            return Cache::has("blacklist:{$prefix}:{$tokenHash}");
        } catch (\Exception $e) {
            Log::warning("Failed to check token blacklist ({$prefix})", ['error' => $e->getMessage()]);
            return false;
        }
    }
}

