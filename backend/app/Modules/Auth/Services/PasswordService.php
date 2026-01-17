<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\DBCore\Models\Core\User;

/**
 * Password Service
 *
 * Handles password change functionality for authenticated users.
 * FetchIt uses Google OAuth, but supports password changes for users with local passwords.
 */
class PasswordService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Change password for authenticated user
     *
     * Updates password in local database.
     * Note: FetchIt primarily uses Google OAuth, but supports password changes for users with local passwords.
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Validate current password
        if (!$user->password || !Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Current password is incorrect');
        }

        // Hash new password for local storage
        $hashedPassword = Hash::make($newPassword);

        return DB::transaction(function () use ($user, $hashedPassword) {
            // Update password in local database
            $user->updatePassword($hashedPassword);

            return [
                'success' => true,
                'message' => 'Password changed successfully',
                'email' => $user->email,
            ];
        });
    }
}
