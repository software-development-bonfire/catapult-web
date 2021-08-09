<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Eloquent\SystemLogRepository;
use App\Entities\SystemLog;
use App\Validators\SystemLogValidator;

/**
 * Class SystemLogRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class SystemLogRepositoryEloquent extends BaseRepository implements SystemLogRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SystemLog::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
