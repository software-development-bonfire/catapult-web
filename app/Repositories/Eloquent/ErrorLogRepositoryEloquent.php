<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\ErrorLogRepository;
use App\Entities\ErrorLog;
use App\Validators\ErrorLogValidator;
use Carbon\Carbon;

/**
 * Class ErrorLogRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ErrorLogRepositoryEloquent extends BaseRepository implements ErrorLogRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ErrorLog::class;
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
        $from = Carbon::parse($filters['from'])->format('Y-m-d');
        $to = Carbon::parse($filters['to'])->format('Y-m-d');
        if (isset($filters['status'])) {
            $status = $filters['status'] == 1 ? 'Resolved' : 'Failed Conversion';

            $this->model = $this->model
                ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->where('status', $status)
                ->orderBy('created_at', 'DESC');
        } else {
            $this->model = $this->model
                ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->orderBy('created_at', 'DESC');
        }
        
        return $this->paginate($filters['itemsPerPage']);
    }
    
}
