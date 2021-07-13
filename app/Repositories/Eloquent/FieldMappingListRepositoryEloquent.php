<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\FieldMappingListRepository;
use App\Entities\FieldMappingList;
use App\Validators\FieldMappingListValidator;

/**
 * Class FieldMappingListRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class FieldMappingListRepositoryEloquent extends BaseRepository implements FieldMappingListRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return FieldMappingList::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function list($filters)
    {
        $this->model = $this->model
            ->with(['remoteSetup', 'catapultDBSetup', 'apiSetup', 'dataMappings'])
            ->select([
                'bid',
                'field_mapping_bid',
                'remote_setup_bid',
                'catapult_db_setup_bid',
                'api_setup_bid',
                'name',
                'type',
                'status',
                'api_endpoint',
                'api_version_name',
            ])
            ->orderBy('bid', 'ASC');

        return $this->paginate($filters['itemsPerPage']);
    }
}
