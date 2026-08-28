<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface KitchenStationRepository.
 *
 * @package namespace App\Repositories;
 */
interface KitchenStationRepository extends RepositoryInterface
{
    public function list($filters, $includeDevice = false);
}
