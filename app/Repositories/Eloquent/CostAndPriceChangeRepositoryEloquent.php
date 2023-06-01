<?php

namespace App\Repositories\Eloquent;

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

        if (! empty($filters)) {
            if (isset($filters->type) && $filters->type !== '') {
                $this->model = $this->model->where('type', $filters->type);
            }
            if (isset($filters->pricing_type) && $filters->pricing_type !== '') {
                $this->model = $this->model->where('pricing_type', $filters->pricing_type);
            }
            if (! empty($filters->status)) {
                $this->model = $this->model->where('status', $filters->status);
            }
            if (isset($filters->is_generated) && $filters->is_generated !== '') {
                $this->model = $this->model->where('is_generated', $filters->is_generated);
            }
            if (! empty($filters->effective_at) && ! empty($filters->expires_at)) {
                $effectiveAt = parseDateTime($filters->effective_at, 'Y-m-d h:i:s', '');
                $expiresAt = parseDateTime($filters->expires_at, 'Y-m-d h:i:s', '');
                $this->model = $this->model->whereBetween('expires_at', [$effectiveAt, $expiresAt]);
            } else {
                if (! empty($filters->effective_at)) {
                    $effectiveAt = parseDateTime($filters->effective_at, 'Y-m-d', '');
                    $this->model = $this->model->whereBetween('effective_at', ["{$effectiveAt} 00:00:00", "{$effectiveAt} 23:59:59"]);
                }
                if (! empty($filters->expires_at)) {
                    $expiresAt = parseDateTime($filters->expires_at, 'Y-m-d', '');
                    $this->model = $this->model->whereBetween('effective_at', ["{$expiresAt} 00:00:00", "{$expiresAt} 23:59:59"]);
                }
            }
        }

        Log::alert($this->getSqlWithBindings($this->model));
        return $this->model->get();
    }
}
