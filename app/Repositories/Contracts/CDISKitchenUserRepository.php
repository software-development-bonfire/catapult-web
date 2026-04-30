<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface KitchenDisplayRepository.
 *
 * @package namespace App\Repositories;
 */
interface CDISKitchenUserRepository extends RepositoryInterface
{
    /**
     * Authenticate a kitchen user by passcode with branch validation.
     *
     * @param  string $passcode
     * @return array|null  ['user' => CDISKitchenUser, 'allowed_branches' => Collection] or null
     */
    public function authenticateByPasscode(string $passcode): ?array;

    /**
     * Authenticate a kitchen user by username/password with branch validation.
     *
     * @param  string $username
     * @param  string $password
     * @return array|null  ['user' => CDISKitchenUser, 'allowed_branches' => Collection] or null
     */
    public function authenticateByCredentials(string $username, string $password): ?array;
}
