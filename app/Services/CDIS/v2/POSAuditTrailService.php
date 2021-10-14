<?php

namespace App\Services\CDIS\v2;

use App\Entities\CDISPOSAuditTrail;
use App\Repositories\Contracts\CDIS\BranchRepository;
use App\Repositories\Contracts\CDIS\POSAuditTrailRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class POSAuditTrailService
{
    use DatabaseTransaction;

    /**
     * Store audit trail
     *
     * @param array $data
     * @return DatabaseTransaction
     */
    public function store($data)
    {
        return $this->transaction(function() use($data) {
            foreach ($data as $headIndex => $headData) {
                foreach ($headData as $datumIndex => $datum) {
                    $datum = (object)$datum;
                    $datum = (object)$datum;

                    $branch = app()->make(BranchRepository::class)
                        ->with(['terminals' => function ($query) use ($datum) {
                            $query->where('number', $datum->terminal_number);

                            return $query;
                        }])
                        ->findWhere([
                            'code' => $datum->branch_code
                        ])
                        ->first();

                    $terminal = $branch->terminals;

                    $terminalBid = $terminal[0]->bid;

                    $entryData = [
                        'terminal_bid' => $terminalBid,
                        'log_id' => $datum->log_id,
                        'date' => date('Y-m-d H:i:s', strtotime($datum->date)),
                        'application' => $datum->application,
                        'cashier' => $datum->cashier,
                        'supervisor' => $datum->supervisor,
                        'job' => $datum->job,
                        'transaction_no' => $datum->transaction_no,
                        'receipt_no' => $datum->receipt_no,
                        'remarks' => $datum->remarks,
                    ];

                    $posAuditTrail = app()->make(POSAuditTrailRepository::class)
                        ->findWhere([
                            'terminal_bid' => $terminal[0]->bid,
                            'log_id' => $datum->log_id,
                        ]);

                    if ($posAuditTrail->count() > 0) {
                        $posAuditTrail[0]->delete();
                    }

                    $cdisPosAuditTrail = CDISPOSAuditTrail::create($entryData);

                    foreach ($entryData as $entryDatumKey => $entryDatum) {
                        $data[$headIndex][$datumIndex][$entryDatumKey] = $entryDatum;
                    }

                    unset($data[$headIndex][$datumIndex]['terminal_number']);
                    unset($data[$headIndex][$datumIndex]['branch_code']);
                    $data[$headIndex][$datumIndex]['bid'] = $cdisPosAuditTrail->bid;
                    $data[$headIndex][$datumIndex]['created_at'] =
                        ! is_null($cdisPosAuditTrail->created_at)
                            ? Carbon::parse($cdisPosAuditTrail->created_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['updated_at'] =
                        ! is_null($cdisPosAuditTrail->updated_at)
                            ? Carbon::parse($cdisPosAuditTrail->updated_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['deleted_at'] =
                        ! is_null($cdisPosAuditTrail->deleted_at)
                            ? Carbon::parse($cdisPosAuditTrail->deleted_at)->format('Y-m-d H:i:s')
                            : null;
                }
            }

            return $data;
        });
    }
}
