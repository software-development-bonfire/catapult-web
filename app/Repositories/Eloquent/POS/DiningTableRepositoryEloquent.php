<?php

namespace App\Repositories\Eloquent\POS;

use App\Entities\DiningTable;
use App\Repositories\Contracts\POS\DiningTableRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class DiningTableRepositoryEloquent extends BaseRepository implements DiningTableRepository
{
    public function model()
    {
        return DiningTable::class;
    }

    public function findByNameAndLocation(string $name, ?int $locationId = null)
    {
        return $this->model
            ->where('name', $name)
            ->when(!is_null($locationId), function ($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->first();
    }

    public function updateOrCreateById(?int $id, array $data)
    {
        if (!empty($id)) {
            $record = $this->model->find($id);
            if ($record) {
                $record->update($data);
                return $record->fresh();
            }
        }

        return $this->model->create($data);
    }

    public function findByIdOrName($id, $name, $locationId = null)
    {
        return $this->model
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', $id);
            })
            ->when(empty($id) && !empty($name), function ($query) use ($name, $locationId) {
                $query->where('name', $name);
                if (!is_null($locationId)) {
                    $query->where('location_id', $locationId);
                }
            })
            ->first();
    }
}
