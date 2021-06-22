<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Entities\FieldMapping;
use App\Validators\FieldMappingValidator;

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
            ->with('details')
            ->withCount('details')
            ->where('type', 'like', '%'.$filters['mapping_type'].'%')
            ->where('status', 'like', '%'.$filters['status'].'%')
            ->orderBy('bid', 'ASC');

        return $this->paginate($filters['itemsPerPage']);
    }
    
}
