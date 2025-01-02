<?php

namespace App\Repositories\Eloquent;

use App\Entities\SyncEntry;
use App\Repositories\Contracts\SyncEntryRepository;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class SyncEntryAliasEloquent.
 *
 * @package namespace App\Repositories;
 */
class SyncEntryRepositoryEloquent extends BaseRepository implements SyncEntryRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SyncEntry::class;
    }

    public function list($filters = [])
    {
        $filters = (object) $filters;

        $this->model = $this->model
            ->orderBy('name', 'ASC');

        if (! is_null($filters->type) && $filters->type != '') {
            $this->model = $this->model->where('type', $filters->type);
        }

        return $this->model->get();
    }
}
