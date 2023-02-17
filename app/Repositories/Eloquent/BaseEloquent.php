<?php

namespace App\Repositories\Eloquent;

use App\Entities\Base;
use App\Repositories\Contracts\BaseRepository;
use App\Traits\GenericHelper;
use App\Traits\QueryHelper;
use Prettus\Repository\Eloquent\BaseRepository AS PrettusBaseRepository;

class BaseEloquent extends PrettusBaseRepository implements BaseRepository
{
    use QueryHelper,
        GenericHelper;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Base::class;
    }

    /**
     * Load relation with closure
     *
     * @param string $relation
     * @param closure $closure
     *
     * @return $this
     */
    public function orWhereHas($relation, $closure)
    {
        $this->model = $this->model->orWhereHas($relation, $closure);

        return $this;
    }
}
