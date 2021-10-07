<?php

namespace App\Criteria\FieldMapping;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ListCriteria.
 *
 * @package namespace App\Criteria\FieldMappingList;
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

        if (isset($filters->bid) && $filters->bid != '') {
            $model->where('field_mapping.bid', $filters->bid);
        }

        if (isset($filters->data_entry) && $filters->data_entry != '') {
            $model->where('field_mapping.data_entry', $filters->data_entry);
        }

        if (isset($filters->mapping_type) && $filters->mapping_type != '') {
            $model->where('field_mapping.mapping_type', $filters->mapping_type);
        }

        if (isset($filters->status) && $filters->status != '') {
            $model->where('field_mapping.status', $filters->status);
        }

        return $model;
    }
}
