<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\DataMappingRepository;
use App\Entities\DataMapping;
use App\Validators\DataMappingValidator;

/**
 * Class DataMappingRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class DataMappingRepositoryEloquent extends BaseRepository implements DataMappingRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return DataMapping::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
