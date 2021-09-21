<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface KitchenStationProcessRepository.
 *
 * @package namespace App\Repositories;
 */
interface KitchenStationProcessRepository extends RepositoryInterface
{
    public function list($filters);
}
