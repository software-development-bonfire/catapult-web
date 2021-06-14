<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\CatapultDbSetupRepository;
use App\Entities\CatapultDbSetup;
use App\Validators\CatapultDbSetupValidator;

/**
 * Class CatapultDbSetupRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class CatapultDbSetupRepositoryEloquent extends BaseRepository implements CatapultDbSetupRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CatapultDbSetup::class;
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
                'host',
                'port',
                'db_name',
                'username',
                'password',
                'status',
            ])
            ->orderBy('id', 'ASC');

        return  $this->paginate($filters['itemsPerPage']);
    }
    
}
