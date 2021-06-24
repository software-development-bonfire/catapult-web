<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\UserAccountRepository;
use App\User;
use App\Validators\UserAccountValidator;

/**
 * Class UserAccountRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class UserAccountRepositoryEloquent extends BaseRepository implements UserAccountRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return User::class;
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
        ->with('permissions')
            ->select([
                'id',
                'bid',
                'name',
                'username',
                'status'
            ])
            ->orderBy('bid', 'ASC');

        return $this->paginate($filters['itemsPerPage']);
    }
}
