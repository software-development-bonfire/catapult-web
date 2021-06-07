<?php

namespace App\Observers;

use Illuminate\Support\Facades\DB;

/**
 * Class BidObserver
 * @package App\Observers
 */
class BidObserver
{
    /**
     * @param $model
     */
    public function creating($model)
    {
        $model->bid = $this->getNextBid($model->getTable());
    }

    /**
     * Get next BID
     *
     * @param $table
     * @return mixed
     */
    private function getNextBid($table)
    {
        $bidPrefix = config('configuration.client_id');

        DB::statement(DB::raw("SET @min := ".$bidPrefix."000000000000000;"));
        DB::statement(DB::raw("SET @max := ".$bidPrefix."999999999999999;"));

        $bid = DB::selectOne(
            "SELECT 
                COALESCE(MAX(`bid`), @min) + 1 AS `bid`
            FROM
                `".$table."`
            WHERE
                `bid` >= @min AND `bid` <= @max
            FOR UPDATE;"
        )->bid;

        return $bid;
    }
}

