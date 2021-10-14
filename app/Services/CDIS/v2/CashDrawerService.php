<?php

namespace App\Services\CDIS\v2;

use App\Entities\CDISCashDrawer;
use App\Repositories\Contracts\CDIS\BranchRepository;
use App\Repositories\Contracts\CDIS\CashDrawerRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class CashDrawerService
{
    use DatabaseTransaction;

    /**
     * Store cash drawer
     *
     * @param array $data
     * @return DatabaseTransaction
     */
    public function store($data)
    {
        return $this->transaction(function() use($data) {
            foreach ($data as $headIndex => $headData) {
                foreach ($headData as $datumIndex => $datum) {
                    $datum = (object) $datum;

                    $branch = app()->make(BranchRepository::class)
                        ->with(['terminals' => function($query) use($datum) {
                            $query->where('number', $datum->terminal_number);

                            return $query;
                        }])
                        ->findWhere([
                            'code' => $datum->branch_code
                        ])
                        ->first();

                    $terminal = $branch->terminals;

                    $cashDrawerData = [
                        'terminal_bid' => $terminal[0]->bid,
                        'cashier_bid' => $datum->cashier_id,
                        'cashier_name' => $datum->cashier_name,
                        'amount' => $datum->amount,
                        'date' => $datum->date,
                        'approver_bid' => $datum->approver_id,
                        'approver_name' => $datum->approver_name,
                        'approved_date' => $datum->approved_date,
                        'type' => $datum->type,
                        'remarks' => $datum->remarks,
                    ];

                    $cashDrawer = app()->make(CashDrawerRepository::class)
                            ->findWhere($cashDrawerData);

                    if ($cashDrawer->count() > 0) {
                        $cashDrawer[0]->delete();
                    }

                    $cashDrawerCreated = CDISCashDrawer::create($cashDrawerData);

                    foreach ($cashDrawerData as $cashDrawerDatumKey => $cashDrawerDatum) {
                        $data[$headIndex][$datumIndex][$cashDrawerDatumKey] = $cashDrawerDatum;
                    }

                    unset($data[$headIndex][$datumIndex]['terminal_number']);
                    unset($data[$headIndex][$datumIndex]['branch_code']);
                    $data[$headIndex][$datumIndex]['bid'] = $cashDrawerCreated->bid;
                    $data[$headIndex][$datumIndex]['created_at'] =
                        ! is_null($cashDrawerCreated->created_at)
                            ? Carbon::parse($cashDrawerCreated->created_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['updated_at'] =
                        ! is_null($cashDrawerCreated->updated_at)
                            ? Carbon::parse($cashDrawerCreated->updated_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['deleted_at'] =
                        ! is_null($cashDrawerCreated->deleted_at)
                            ? Carbon::parse($cashDrawerCreated->deleted_at)->format('Y-m-d H:i:s')
                            : null;
                }
            }

            return $data;
        });
    }
}
