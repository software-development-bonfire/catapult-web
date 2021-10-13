<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\FileStorageSetupRepository;
use App\Entities\FileStorageSetup;

/**
 * Class FileStorageSetupRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class FileStorageSetupRepositoryEloquent extends BaseRepository implements FileStorageSetupRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return FileStorageSetup::class;
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
                'bid',
                'name',
                'storage_type',
                'local_path',
                'remote_path',
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
