<?php

namespace App\Repositories\Contracts\POS;

use Prettus\Repository\Contracts\RepositoryInterface;

interface TableLocationRepository extends RepositoryInterface
{
    public function findByName(string $name);

    public function updateOrCreateById(?int $id, array $data);

    public function updateOrCreateByPosId(?int $posId, array $data);
}
