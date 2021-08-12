<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\SystemLogRepository;
use App\Entities\SystemLog;
use App\Validators\SystemLogValidator;
use Carbon\Carbon;

/**
 * Class SystemLogRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class SystemLogRepositoryEloquent extends BaseRepository implements SystemLogRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SystemLog::class;
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
        $from = Carbon::parse($filters['from'])->format('Y-m-d').' 00:00:00';
        $to = Carbon::parse($filters['to'])->format('Y-m-d').' 23:59:59';

        $userFilter = false;
        $moduleFilter = false;

        if (isset($filters['users'])) {
            $users = [];

            foreach($filters['users'] as $user) {
                $users[] =  json_decode($user)->value;
            }
            
            if (! in_array('All', $users)) {
                $userFilter = true;
            }
        }

        if (isset($filters['modules'])) {
            $modules = [];

            foreach($filters['modules'] as $module) {
                $modules[] =  json_decode($module)->value;
            }

            if (! in_array('All', $modules)) {
                $userFilter = true;
            }
        }

        if ($userFilter && $moduleFilter) {
            $this->model = $this->model
                ->whereIn('initiator', $users)
                ->whereIn('module_process', $modules)
                ->whereBetween('created_at', [$from, $to])
                ->select([
                    'bid',
                    'initiator',
                    'module_process',
                    'action',
                    'description',
                    'created_at',
                ])
                ->orderBy('bid', 'DESC');
        } else if ($userFilter && ! $moduleFilter) {
            $this->model = $this->model
                ->whereIn('initiator', $users)
                ->whereBetween('created_at', [$from, $to])
                ->select([
                    'bid',
                    'initiator',
                    'module_process',
                    'action',
                    'description',
                    'created_at',
                ])
                ->orderBy('bid', 'DESC');
        } else if (! $userFilter && $moduleFilter) {
            $this->model = $this->model
                ->whereIn('module_process', $modules)
                ->whereBetween('created_at', [$from, $to])
                ->select([
                    'bid',
                    'initiator',
                    'module_process',
                    'action',
                    'description',
                    'created_at',
                ])
                ->orderBy('bid', 'DESC');
        } else {
            $this->model = $this->model
                ->whereBetween('created_at', [$from, $to])
                ->select([
                    'bid',
                    'initiator',
                    'module_process',
                    'action',
                    'description',
                    'created_at',
                ])
                ->orderBy('bid', 'DESC');
        }

        return $this->paginate($filters['itemsPerPage']);
    }
    
}
