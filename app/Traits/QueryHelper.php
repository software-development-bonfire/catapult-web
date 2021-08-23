<?php

namespace App\Traits;

use App\Entities\Base;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

trait QueryHelper
{
    /**
     * Create sub query
     *
     * @param $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function createSubQuery($query)
    {
        return DB::table(DB::raw("(".$query->toSql().") as inner_query"))
            ->mergeBindings($query)
            ->select('*');
    }

    /**
     * Generate a LengthAwarePaginator class based from the count of Builder class
     *
     * @param Builder $query
     * @return LengthAwarePaginator
     */
    protected function createPaginationFromQuery(Builder $query)
    {
        $request = app()->make('request');

        $perPage = $request->get('itemsPerPage', 10);
        $page = $request->get('page', 1);

        $count = $query->getCountForPagination();

        $data = $query->forPage($page, $perPage)->get();

        return new LengthAwarePaginator($data, $count, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Hydrate items in the paginator collection to the specified class
     *
     * @param LengthAwarePaginator $paginator
     * @param Base $class
     * @return LengthAwarePaginator
     */
    protected function hydratePaginationItems(LengthAwarePaginator $paginator, Base $class)
    {
        $items = [];

        foreach ($paginator->items() as $key => $item) {
            $items[] = (get_class($item) === 'stdClass')
                ? (array) $item
                : $item->toArray();
        }

        return $paginator->setCollection($class::hydrate($items));
    }
}
