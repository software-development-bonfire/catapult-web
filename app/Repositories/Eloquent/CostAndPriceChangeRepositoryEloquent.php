<?php

namespace App\Repositories\Eloquent;

use App\Criteria\CostAndPriceChange\ListCriteria;
use App\Entities\CDISCostAndPriceChange;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\CostAndPriceChangeRepository;
use App\Traits\GenericHelper;
use Illuminate\Support\Facades\Log;

/**
 * Class CostAndPriceChangeRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class CostAndPriceChangeRepositoryEloquent extends BaseRepository implements CostAndPriceChangeRepository
{
    use GenericHelper;
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CDISCostAndPriceChange::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * Get list of Cost and Price Change
     *
     * @param object $filters
     * @return Collection $result.
     */
    public function list($filters = null)
    {
        $this->model = $this->model
            ->select([
                'bid',
                'code',
                'type',
                'pricing_type',
                'vendor_bid',
                'effective_at',
                'expires_at',
                'status',
                'is_generated',
                'generated_at',
            ])
            ->orderBy('id', 'ASC');

        $this->pushCriteria(new ListCriteria($filters))->applyCriteria();

        return $this->model->get();
    }
}
