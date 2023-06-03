<?php

namespace App\Criteria\CostAndPriceChange;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ListCriteria.
 *
 * @package namespace App\Criteria\CostAndPriceChange;
 */
class ListCriteria implements CriteriaInterface
{
    /**
     * @var array|mixed
     */
    protected $filters;

    /**
     * @param array|mixed $filters
     */
    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Apply criteria in query repository
     *
     * @param object              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $filters = $this->filters;

        if (isset($filters->type) && $filters->type !== '') {
            $model->where('type', $filters->type);
        }
        if (isset($filters->pricing_type) && $filters->pricing_type !== '') {
            $model->where('pricing_type', $filters->pricing_type);
        }
        if (! empty($filters->status)) {
            $model->where('status', $filters->status);
        }
        if (isset($filters->is_generated) && $filters->is_generated !== '') {
            $model->where('is_generated', $filters->is_generated);
        }
        if (! empty($filters->effective_at) && ! empty($filters->expires_at)) {
            $effectiveAt = parseDateTime($filters->effective_at, 'Y-m-d h:i:s', '');
            $expiresAt = parseDateTime($filters->expires_at, 'Y-m-d h:i:s', '');
            $model->whereBetween('expires_at', [$effectiveAt, $expiresAt]);
        } else {
            if (! empty($filters->effective_at)) {
                $effectiveAt = parseDateTime($filters->effective_at, 'Y-m-d', '');
                $model->whereBetween('effective_at', ["{$effectiveAt} 00:00:00", "{$effectiveAt} 23:59:59"]);
            }
            if (! empty($filters->expires_at)) {
                $expiresAt = parseDateTime($filters->expires_at, 'Y-m-d', '');
                $model->whereBetween('effective_at', ["{$expiresAt} 00:00:00", "{$expiresAt} 23:59:59"]);
            }
        }
        return $model;
    }
}
