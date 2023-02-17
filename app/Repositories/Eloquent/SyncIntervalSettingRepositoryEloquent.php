<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\SyncIntervalSettingRepository;
use App\Entities\SyncIntervalSetting;
use App\Validators\SyncIntervalSettingValidator;

/**
 * Class SyncIntervalSettingRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class SyncIntervalSettingRepositoryEloquent extends BaseRepository implements SyncIntervalSettingRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SyncIntervalSetting::class;
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
            ->select([
                'bid',
                'name',
                'checking_interval',
                'syncing_type',
                'start_time',
                'status',
                'created_by',
                'updated_by'
            ])
            ->orderBy('id', 'ASC');

        return $this->paginate($filters['itemsPerPage']);
    }
    
}
