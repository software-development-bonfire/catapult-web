<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\ErrorLogDetailRepository;
use App\Entities\ErrorLogDetail;
use App\Validators\ErrorLogDetailValidator;

/**
 * Class ErrorLogDetailRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ErrorLogDetailRepositoryEloquent extends BaseRepository implements ErrorLogDetailRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ErrorLogDetail::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
