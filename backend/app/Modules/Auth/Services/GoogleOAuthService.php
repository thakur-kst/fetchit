<?php

namespace Modules\Auth\Services;

use Google_Client;
use Modules\DBCore\Models\Core\User;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Google OAuth Service
 * 
 * Handles Google ID token verification and user creation/updates.
 */
class GoogleOAuthService
{
    private Google_Client $client;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
        $this->client = new Google_Client(['client_id' => config('services.google.client_id')]);
    }

    /**
     * Verify Google ID token and return user data
     * 
     * @param string $idToken Google ID token
     * @return array|null User data or null if invalid
     */
    public function verifyIdToken(string $idToken): ?array
    {
        try {
            $payload = $this->client->verifyIdToken($idToken);
            
            if (!$payload) {
                Log::warning('Invalid Google ID token');
                return null;
            }

            return [
                'google_id' => $payload['sub'],
                'email' => $payload['email'],
                'email_verified' => $payload['email_verified'] ?? false,
                'name' => $payload['name'] ?? null,
                'picture' => $payload['picture'] ?? null,
                'locale' => $payload['locale'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Error verifying Google ID token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find or create user from Google token data
     * 
     * @param array $googleData Verified Google token data
     * @return User
     */
    public function findOrCreateUser(array $googleData): User
    {
        return DB::transaction(function () use ($googleData) {
            // Try to find by google_id first
            $user = $this->userRepository->findByGoogleId($googleData['google_id']);

            if (!$user) {
                // Try to find by email
                $user = $this->userRepository->findByEmail($googleData['email']);
            }

            if ($user) {
                // Update user with Google data
                $user->update([
                    'google_id' => $googleData['google_id'],
                    'email' => $googleData['email'],
                    'email_verified_at' => $googleData['email_verified'] ? now() : null,
                    'name' => $googleData['name'] ?? $user->name,
                    'picture' => $googleData['picture'] ?? $user->picture,
                    'locale' => $googleData['locale'] ?? $user->locale ?? 'en',
                ]);
            } else {
                // Create new user using User model directly
                $user = User::create([
                    'google_id' => $googleData['google_id'],
                    'email' => $googleData['email'],
                    'email_verified_at' => $googleData['email_verified'] ? now() : null,
                    'name' => $googleData['name'] ?? $googleData['email'],
                    'picture' => $googleData['picture'],
                    'locale' => $googleData['locale'] ?? 'en',
                    'status' => 'active',
                ]);
            }

            return $user;
        });
    }
}
