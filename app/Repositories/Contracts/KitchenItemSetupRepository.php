<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface KitchenItemSetupRepository.
 *
 * @package namespace App\Repositories;
 */
interface KitchenItemSetupRepository extends RepositoryInterface
{
    public function list($filters);
    public function details($filters);
}
