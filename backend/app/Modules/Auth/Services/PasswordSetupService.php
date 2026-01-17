<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\DBCore\Models\Core\User;

/**
 * Password Setup Service
 *
 * Handles password setup for new users via email token.
 * Note: This service is deprecated for FetchIt as we use Google OAuth.
 * Kept for backward compatibility if needed.
 */
class PasswordSetupService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Setup password using token
     *
     * @param string $token Password setup token
     * @param string $password New password
     * @param string $confirmPassword Password confirmation
     * @return array
     */
    public function setupPassword(string $token, string $password, string $confirmPassword): array
    {
        // This method is deprecated for FetchIt
        // FetchIt uses Google OAuth for authentication
        throw new \Exception('Password setup via token is not supported. Please use Google OAuth to authenticate.');
    }
}
