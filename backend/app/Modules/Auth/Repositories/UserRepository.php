<?php

namespace Modules\Auth\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\DBCore\Models\Core\User;
use Modules\Shared\Repositories\BaseRepository;

/**
 * User Repository
 *
 * Repository implementation for User model operations.
 *
 * @package Modules\Auth\Repositories
 * @version 1.0.0
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected string $model = User::class;

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->getModel()::where('email', $email)->first();
    }

    /**
     * Find user by email (case-insensitive)
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmailCaseInsensitive(string $email): ?User
    {
        // Try exact match first
        $user = $this->findByEmail($email);
        if ($user) {
            return $user;
        }

        // Try case-insensitive search using database function
        $connection = DB::connection()->getDriverName();
        if ($connection === 'pgsql') {
            return $this->getModel()::whereRaw('email ILIKE ?', [$email])->first();
        }

        return $this->getModel()::whereRaw('LOWER(email) = LOWER(?)', [$email])->first();
    }

    /**
     * Create user with password
     *
     * @param string $email
     * @param string $hashedPassword
     * @param string $name
     * @param string $firstName
     * @param string $lastName
     * @return User
     */
    public function createWithPassword(
        string $email,
        string $hashedPassword,
        string $name,
        string $firstName,
        string $lastName
    ): User {
        return User::createWithPassword(
            $email,
            $hashedPassword,
            $name,
            $firstName,
            $lastName
        );
    }

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $hashedPassword
     * @return bool
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        return $user->updatePassword($hashedPassword);
    }

    /**
     * Find user by Google ID
     *
     * @param string $googleId
     * @return User|null
     */
    public function findByGoogleId(string $googleId): ?User
    {
        return $this->getModel()::where('google_id', $googleId)->first();
    }
}

