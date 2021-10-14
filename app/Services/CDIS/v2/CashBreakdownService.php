<?php

namespace App\Services\CDIS\v2;

use App\Entities\CDISCashBreakdown;
use App\Repositories\Contracts\CDIS\BranchRepository;
use App\Repositories\Contracts\CDIS\CashBreakdownRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class CashBreakdownService
{
    use DatabaseTransaction;

    /**
     * Store cash break down
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

                    if (
                        $datum->approver_id == 0
                        && is_null($datum->approved_date)
                        && is_null($datum->approver_name)
                    ) {
                        $datum->approver_id = $datum->cashier_id;
                        $datum->approver_name = $datum->cashier_name;
                        $datum->approved_date = $datum->date;
                    }

                    $headData = [
                        'terminal_bid' => $terminal[0]->bid,
                        'cashier_bid' => $datum->cashier_id,
                        'cashier_name' => $datum->cashier_name,
                        'date' => $datum->date,
                        'approver_bid' => $datum->approver_id,
                        'approver_name' => $datum->approver_name,
                        'approved_date' => $datum->approved_date,
                        'remarks' => $datum->remarks,
                    ];

                    $cashBreakdown = app()->make(CashBreakdownRepository::class)
                        ->findWhere($headData);

                    if ($cashBreakdown->count() > 0) {
                        $cashBreakdown = $cashBreakdown->first();
                        $cashBreakdown->update($headData);
                        $cashBreakdown->details()->delete();
                    } else {
                        $cashBreakdown = CDISCashBreakdown::create($headData);
                    }

                    foreach ($headData as $headDatumKey => $headDatum) {
                        $data[$headIndex][$datumIndex][$headDatumKey] = $headDatum;
                    }

                    unset($data[$headIndex][$datumIndex]['terminal_number']);
                    unset($data[$headIndex][$datumIndex]['branch_code']);
                    $data[$headIndex][$datumIndex]['bid'] = $cashBreakdown->bid;
                    $data[$headIndex][$datumIndex]['created_by'] = $cashBreakdown->created_by;
                    $data[$headIndex][$datumIndex]['updated_by'] = $cashBreakdown->updated_by;
                    $data[$headIndex][$datumIndex]['created_at'] =
                        ! is_null($cashBreakdown->created_at)
                            ? Carbon::parse($cashBreakdown->created_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['updated_at'] =
                        ! is_null($cashBreakdown->updated_at)
                            ? Carbon::parse($cashBreakdown->updated_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['deleted_at'] =
                        ! is_null($cashBreakdown->deleted_at)
                            ? Carbon::parse($cashBreakdown->deleted_at)->format('Y-m-d H:i:s')
                            : null;

                    foreach ($datum->detail as $detailIndex => $detail) {
                        $detail = (object) $detail;

                        $cashBreakdownDetailData = [
                            'head_bid' => $cashBreakdown->bid,
                            'denomination' => $detail->denomination,
                            'quantity' => $detail->quantity,
                            'amount' => $detail->amount,
                        ];

                        $cashBreakdownDetail =  $cashBreakdown->details()->create($cashBreakdownDetailData);

                        unset($data[$headIndex][$datumIndex]['terminal_number']);
                        unset($data[$headIndex][$datumIndex]['branch_code']);
                        $data[$headIndex][$datumIndex]['detail'][$detailIndex]['bid'] = $cashBreakdownDetail->bid;
                        $data[$headIndex][$datumIndex]['detail'][$detailIndex]['created_at'] =
                            ! is_null($cashBreakdownDetail->created_at)
                                ? Carbon::parse($cashBreakdownDetail->created_at)->format('Y-m-d H:i:s')
                                : null;
                        $data[$headIndex][$datumIndex]['detail'][$detailIndex]['updated_at'] =
                            ! is_null($cashBreakdownDetail->updated_at)
                                ? Carbon::parse($cashBreakdownDetail->updated_at)->format('Y-m-d H:i:s')
                                : null;
                        $data[$headIndex][$datumIndex]['detail'][$detailIndex]['deleted_at'] =
                            ! is_null($cashBreakdownDetail->deleted_at)
                                ? Carbon::parse($cashBreakdownDetail->deleted_at)->format('Y-m-d H:i:s')
                                : null;
                    }
                }
            }

            return $data;
        });
    }
}
