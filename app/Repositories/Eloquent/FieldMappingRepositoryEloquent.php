<?php

namespace App\Repositories\Eloquent;

use App\Criteria\FieldMapping\ListCriteria as FieldMappingListListCriteria;
use App\Criteria\FieldMapping\ListCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Entities\FieldMapping;

/**
 * Class FieldMappingRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class FieldMappingRepositoryEloquent extends BaseRepository implements FieldMappingRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return FieldMapping::class;
    }

    public function list(
        $filters,
        $isTablePaginate = true,
        $with = ['remoteSetup', 'catapultDBSetup', 'apiSetup', 'dataMappings']
    ) {
        $this->model = $this->model
            ->with($with)
            ->select([
                'bid',
                'remote_setup_bid',
                'catapult_db_setup_bid',
                'api_setup_bid',
                'name',
                'type',
                'status',
                'data_entry',
            ])
            ->orderBy('bid', 'ASC');

        $this->pushCriteria(new FieldMappingListListCriteria($filters))->applyCriteria();

        if ($isTablePaginate) {
            return $this->paginate($filters['itemsPerPage']);
        } else {
            return $this->get();
        }
    }

    public function getDetail($filters)
    {
        $this->model = $this->model
            ->with('detail')
            ->orderBy('bid', 'ASC');

        $this->pushCriteria(new ListCriteria($filters))->applyCriteria();

        return $this->first();
    }
}
