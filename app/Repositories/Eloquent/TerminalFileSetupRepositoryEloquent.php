<?php

namespace App\Repositories\Eloquent;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\TerminalFileSetupRepository;
use App\Entities\TerminalFileSetup;
use App\Validators\TerminalFileSetupValidator;

/**
 * Class TerminalFileSetupRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class TerminalFileSetupRepositoryEloquent extends BaseRepository implements TerminalFileSetupRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return TerminalFileSetup::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

     /**
     * Get list of Terminal File Setup
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
                'type',
                'api_setup_bid',
                'terminal_code',
                'terminal_path',
                'sub_directories',
                'status'
            ])
            ->orderBy('id', 'ASC');

        return $this->paginate($filters['itemsPerPage']);
    }
    
}
