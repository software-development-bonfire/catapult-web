<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\ApiSetupRepository;
use App\Entities\ApiSetup;
use App\Validators\ApiSetupValidator;

/**
 * Class ApiSetupRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ApiSetupRepositoryEloquent extends BaseRepository implements ApiSetupRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ApiSetup::class;
    }


    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * Get unit of measurement list
     *
     * @param Array $filters
     * @return Collection $result.
     */
    public function list($filters)
    {
        $this->model = $this->model
            ->select([
                'bid',
                'name',
                'end_point',
                'status',
                'created_by',
                'updated_by'
            ])
            ->orderBy('id', 'ASC');

        if (! empty($filters['itemsPerPage'])) {
            return $this->paginate($filters['itemsPerPage']);
        }
        return $this->model->get();
    }
}
