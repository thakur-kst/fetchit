<?php

namespace Modules\Auth\Services;

use Modules\DBCore\Models\Core\User;
use Modules\Auth\Models\RefreshToken;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * JWT Token Service
 * 
 * Handles JWT token generation and refresh token management using tymon/jwt-auth.
 */
class JwtTokenService
{
    /**
     * Generate access and refresh tokens for user
     * 
     * @param User $user
     * @param string|null $deviceName
     * @param string|null $deviceId
     * @return array
     */
    public function generateTokens(User $user, ?string $deviceName = null, ?string $deviceId = null): array
    {
        try {
            // Generate access token
            $accessToken = JWTAuth::fromUser($user);
            
            // Generate refresh token
            $refreshToken = $this->createRefreshToken($user, $deviceName, $deviceId);
            $plainToken = $refreshToken->getAttribute('plain_token');
            
            $ttl = config('jwt.ttl', 15); // minutes
            
            return [
                'accessToken' => $accessToken,
                'refreshToken' => $plainToken, // Return plain token to client
                'tokenType' => 'Bearer',
                'expiresIn' => $ttl * 60, // seconds
            ];
        } catch (JWTException $e) {
            Log::error('Error generating JWT tokens', [
                'error' => $e->getMessage(),
                'user_id' => $user->uuid,
            ]);
            throw $e;
        }
    }

    /**
     * Create and store refresh token
     * 
     * @param User $user
     * @param string|null $deviceName
     * @param string|null $deviceId
     * @return RefreshToken
     */
    private function createRefreshToken(User $user, ?string $deviceName = null, ?string $deviceId = null): RefreshToken
    {
        // Generate random refresh token
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);
        
        $refreshTtl = config('jwt.refresh_ttl', 43200); // 30 days in minutes
        
        $refreshToken = RefreshToken::create([
            'user_id' => $user->uuid,
            'token_hash' => $tokenHash,
            'device_name' => $deviceName,
            'device_id' => $deviceId,
            'expires_at' => now()->addMinutes($refreshTtl),
        ]);
        
        // Store plain token temporarily (will be returned to client)
        $refreshToken->setAttribute('plain_token', $plainToken);
        
        return $refreshToken;
    }

    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshTokenPlain
     * @return array|null
     */
    public function refreshAccessToken(string $refreshTokenPlain): ?array
    {
        try {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            
            $refreshToken = RefreshToken::where('token_hash', $tokenHash)
                ->whereNull('revoked_at')
                ->first();
            
            if (!$refreshToken || !$refreshToken->isValid()) {
                Log::warning('Invalid or expired refresh token');
                return null;
            }
            
            $user = $refreshToken->user;
            if (!$user) {
                Log::warning('User not found for refresh token');
                return null;
            }
            
            // Revoke old refresh token (token rotation)
            $refreshToken->revoke();
            
            // Generate new tokens
            $deviceName = $refreshToken->device_name;
            $deviceId = $refreshToken->device_id;
            
            $tokens = $this->generateTokens($user, $deviceName, $deviceId);
            
            // Return new refresh token (plain) to client
            return $tokens;
        } catch (\Exception $e) {
            Log::error('Error refreshing access token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Revoke refresh token (logout)
     * 
     * @param string $refreshTokenPlain
     * @return bool
     */
    public function revokeRefreshToken(string $refreshTokenPlain): bool
    {
        try {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            
            $refreshToken = RefreshToken::where('token_hash', $tokenHash)
                ->whereNull('revoked_at')
                ->first();
            
            if ($refreshToken) {
                $refreshToken->revoke();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error revoking refresh token', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get authenticated user from token
     * 
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $user;
        } catch (JWTException $e) {
            return null;
        }
    }
}
