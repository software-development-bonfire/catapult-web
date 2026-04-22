<?php

namespace App\Repositories\Eloquent\POS;

use App\Entities\TableLocation;
use App\Repositories\Contracts\POS\TableLocationRepository;
use Prettus\Repository\Eloquent\BaseRepository;

class TableLocationRepositoryEloquent extends BaseRepository implements TableLocationRepository
{
    public function model()
    {
        return TableLocation::class;
    }

    public function findByName(string $name)
    {
        return $this->model->where('name', $name)->first();
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

    public function updateOrCreateByPosId(?int $posId, array $data)
    {
        if (!empty($posId)) {
            $record = $this->model->where('pos_location_id', $posId)->first();
            if ($record) {
                $record->update($data);
                return $record->fresh();
            }
        }

        return $this->model->create($data);
    }
}
