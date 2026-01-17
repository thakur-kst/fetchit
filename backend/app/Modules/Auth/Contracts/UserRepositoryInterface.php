<?php

namespace Modules\Auth\Contracts;

use Modules\DBCore\Models\Core\User;
use Modules\Shared\Contracts\RepositoryInterface;

/**
 * User Repository Interface
 *
 * Defines contract for User data access operations.
 *
 * @package Modules\Auth\Contracts
 * @version 1.0.0
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by email (case-insensitive)
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmailCaseInsensitive(string $email): ?User;

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
    ): User;

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $hashedPassword
     * @return bool
     */
    public function updatePassword(int $userId, string $hashedPassword): bool;

    /**
     * Find user by Google ID
     *
     * @param string $googleId
     * @return User|null
     */
    public function findByGoogleId(string $googleId): ?User;
}

