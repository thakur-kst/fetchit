<?php

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\DBCore\Models\Core\User;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Services\LaravelJwtService;

/**
 * Login Service
 *
 * Handles user authentication and JWT token generation.
 * FetchIt uses Google OAuth and JWT authentication only.
 */
class LoginService
{
    private ?LaravelJwtService $laravelJwtService = null;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        ?LaravelJwtService $laravelJwtService = null
    ) {
        if ($laravelJwtService === null) {
            $laravelJwtService = app(LaravelJwtService::class);
        }

        $this->laravelJwtService = $laravelJwtService;
    }

    /**
     * Login user with email and password
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $email, string $password): array
    {
        return $this->loginWithJwt($email, $password);
    }

    /**
     * Login with Laravel JWT
     */
    private function loginWithJwt(string $email, string $password): array
    {
        // Validate credentials with Laravel JWT
        $authUser = $this->laravelJwtService->validateUserCredentials($email, $password);

        if (!$authUser) {
            throw new \Exception('Invalid credentials');
        }

        // Find user in local database (case-insensitive)
        $user = $this->findUserByEmailCaseInsensitive($email);

        if (!$user) {
            throw new \Exception('User not found in system');
        }

        // Check if user is active
        if ($user->status !== 'active') {
            throw new \Exception('User account is disabled');
        }

        // Check if this is the first login (last_login is null)
        $isFirstLogin = $user->last_login === null;

        // Update last_login timestamp
        $user->update(['last_login' => now()]);

        return [
            'access_token' => $authUser['token'],
            'refresh_token' => $authUser['refresh_token'] ?? null,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl', 60) * 60, // Convert minutes to seconds
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'email' => $user->email,
                'name' => $user->name,
                'isFirstLogin' => $isFirstLogin,
            ],
        ];
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $refreshToken The refresh token
     * @return array New access token and refresh token
     */
    public function refresh(string $refreshToken): array
    {
        // Laravel JWT refresh
        $result = $this->laravelJwtService->refreshToken($refreshToken);

        if (!$result) {
            throw new \Exception('Invalid or expired refresh token');
        }

        // Find user in local database
        $user = $this->userRepository->find($result['id']);
        if (!$user) {
            throw new \Exception('User not found in system');
        }

        return [
            'access_token' => $result['token'],
            'refresh_token' => $result['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl', 60) * 60, // Convert minutes to seconds
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ];
    }

    /**
     * Logout user
     *
     * @param string $accessToken Access token (Bearer token)
     * @return bool
     */
    public function logout(string $accessToken): bool
    {
        // Laravel JWT logout - validates token and logs the action
        return $this->laravelJwtService->logout($accessToken);
    }

    /**
     * Find user by email (case-insensitive)
     * 
     * @param string $email The email to search for
     * @return User|null
     */
    private function findUserByEmailCaseInsensitive(string $email): ?User
    {
        return $this->userRepository->findByEmailCaseInsensitive($email);
    }
}
