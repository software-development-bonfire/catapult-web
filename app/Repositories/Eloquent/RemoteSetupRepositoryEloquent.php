<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\RemoteSetupRepository;
use App\Entities\RemoteSetup;
use App\Validators\RemoteSetupValidator;

/**
 * Class RemoteSetupRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class RemoteSetupRepositoryEloquent extends BaseRepository implements RemoteSetupRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return RemoteSetup::class;
    }

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
                'id',
                'bid',
                'name',
                'path',
                'server',
                'host',
                'port',
                'username',
                'password',
                'status'
            ])
            ->orderBy('id', 'ASC');

        return $this->paginate($filters['itemsPerPage']);
    }
}
