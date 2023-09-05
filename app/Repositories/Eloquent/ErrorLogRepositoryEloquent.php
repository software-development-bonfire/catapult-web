<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\ErrorLogRepository;
use App\Entities\ErrorLog;
use App\Traits\GenericHelper;
use App\Validators\ErrorLogValidator;
use Illuminate\Support\Facades\Log;

/**
 * Class ErrorLogRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ErrorLogRepositoryEloquent extends BaseRepository implements ErrorLogRepository
{
    use GenericHelper;
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

    public function list($filters, $isTablePaginate = true)
    {
        $this->model = $this->model
            ->with('details')
            ->where(function ($query) use ($filters) {
                $date = parseDateTime($filters->date, 'Y-m-d', now(), true);
                $query->whereRaw("DATE(updated_at) = '{$date}'");
            })
            ->orderBy('id', 'ASC');

        if ($isTablePaginate) {
            return $this->paginate(app()->get('request')->get('itemsPerPage', 10));
        } else {
            return $this->get();
        }
    }
}
