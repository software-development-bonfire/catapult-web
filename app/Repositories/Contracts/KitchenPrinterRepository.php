<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface KitchenPrinterRepository.
 *
 * @package namespace App\Repositories;
 */
interface KitchenPrinterRepository extends RepositoryInterface
{
    public function getMenuPrinters($filters);
}
