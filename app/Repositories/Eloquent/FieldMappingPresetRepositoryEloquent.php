<?php

namespace App\Repositories\Eloquent;

use App\Criteria\FieldMappingPreset\ListCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\FieldMappingPresetRepository;
use App\Entities\FieldMappingPreset;

/**
 * Class FieldMappingPresetRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class FieldMappingPresetRepositoryEloquent extends BaseRepository implements FieldMappingPresetRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return FieldMappingPreset::class;
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
            ->withCount('detail')
            ->orderBy('bid', 'ASC');

        $this->pushCriteria(new ListCriteria($filters))->applyCriteria();

        return $this->paginate($filters['itemsPerPage']);
    }

    public function getDetail($filters)
    {
        $this->model = $this->model
            ->with('detail')
            ->withCount('detail')
            ->orderBy('bid', 'ASC');

        $this->pushCriteria(new ListCriteria($filters))->applyCriteria();

        return $this->first();
    }

    public function getDataEntries($filters)
    {
        $this->model = $this->model
            ->with('detail')
            ->withCount('detail')
            ->orderBy('bid', 'ASC');

        $this->pushCriteria(new ListCriteria($filters))->applyCriteria();

        return $this->paginate(app()->get('request')->get('itemsPerPage', 10));
    }
    
}
