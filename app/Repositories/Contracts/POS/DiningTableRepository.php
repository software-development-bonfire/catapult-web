<?php

namespace App\Repositories\Contracts\POS;

use Prettus\Repository\Contracts\RepositoryInterface;

interface DiningTableRepository extends RepositoryInterface
{
    public function findByNameAndLocation(string $name, ?int $locationId = null);

    public function updateOrCreateById(?int $id, array $data);

    public function findByIdOrName($id, $name, $locationId = null);
}
