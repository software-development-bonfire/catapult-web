<?php

namespace App\Services\CDIS\v2;

use App\Entities\CDISZread;
use App\Repositories\Contracts\CDIS\BranchRepository;
use App\Repositories\Contracts\CDIS\ZReadRepository;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;

class ZReadService
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

                    $zread = app()->make(ZReadRepository::class)
                        ->findWhere([
                            'terminal_bid' => $terminalBid,
                            'log_date' => Carbon::parse($datum->log_date)->format('Y-m-d')
                        ]);

                    if ($zread->count() > 0) {
                        $zread[0]->delete();
                    }

                    $zreadData = [
                        'terminal_bid' => $terminalBid,
                        "day_end_report_number" => $datum->day_end_report_number,
                        "administrator" => $datum->administrator,
                        "cashier" => $datum->cashier,
                        "log_date" => Carbon::parse($datum->log_date)->format('Y-m-d'),
                        "date_time" => Carbon::parse($datum->date_time)->format('Y-m-d H:i:s'),
                        "guest_count" => $datum->guest_count,

                        "gross_sales_amount" => $datum->gross_sales_amount,
                        "transaction_count" => $datum->transaction_count,
                        "mandated_discount_transaction_count" => $datum->mandated_discount_transaction_count,
                        "total_regular_discount_count" => $datum->total_regular_discount_count,
                        "total_regular_discount_amount" => $datum->total_regular_discount_amount,
                        "senior_transaction_count" => $datum->senior_transaction_count,
                        "senior_discount_amount" => $datum->senior_discount_amount,
                        "sc_vat_deduction_amount" => $datum->sc_vat_deduction_amount,
                        "pwd_transaction_count" => $datum->pwd_transaction_count,
                        "pwd_transaction_amount" => $datum->pwd_transaction_amount,
                        "pwd_vat_deduction" => $datum->pwd_vat_deduction,
                        "diplomat_transaction_count" => $datum->diplomat_transaction_count,
                        "diplomat_vat_deduction" => $datum->diplomat_vat_deduction,
                        "vatable_sales" => $datum->vatable_sales,
                        "vat_amount" => $datum->vat_amount,
                        "vat_exempt_sales" => $datum->vat_exempt_sales,
                        "less_mandated_vat_and_discount" => $datum->less_mandated_vat_and_discount,
                        "net_of_vat_exempt" => $datum->net_of_vat_exempt,
                        "zero_rated_sales" => $datum->zero_rated_sales,
                        "non_vat_sales" => $datum->non_vat_sales,
                        "subtotal" => $datum->subtotal,
                        "service_charge" => $datum->service_charge,
                        "net_total" => $datum->net_total,

                        "beginning_or" => $datum->beginning_or,
                        "ending_or" => $datum->ending_or,
                        "no_sales_transaction_count" => $datum->no_sales_transaction_count,
                        "void_transactions_count" => $datum->void_transactions_count,
                        "void_transactions_amount" => $datum->void_transactions_amount,
                        "void_items_count" => $datum->void_items_count,
                        "void_items_amount" => $datum->void_items_amount,
                        "refunds_count" => $datum->refunds_count,
                        "refunds_amount" => $datum->refunds_amount,

                        "total_tenders_count" => $datum->total_tenders_count,
                        "total_tenders_amount" => $datum->total_tenders_amount,
                        "add_initial_cash_amount" => $datum->add_initial_cash_amount,
                        "less_withdrawals_amount" => $datum->less_withdrawals_amount,
                        "add_cash_returns_amount" => $datum->add_cash_returns_amount,
                        "total_drawer_amount" => $datum->total_drawer_amount,

                        "total_cash_breakdown_amount" => $datum->total_cash_breakdown_amount,
                        "short_over_amount" => $datum->short_over_amount,

                        "total_cashier_sales_amount" => $datum->total_cashier_sales_amount,

                        "beginning_balance" => $datum->beginning_balance,
                        "ending_balance" => $datum->ending_balance,
                    ];

                    $zread = CDISZread::create($zreadData);

                    foreach ($zreadData as $zreadDataKey => $zreadDatum) {
                        $data[$headIndex][$datumIndex][$zreadDataKey] = $zreadDatum;
                    }

                    unset($data[$headIndex][$datumIndex]['terminal_number']);
                    unset($data[$headIndex][$datumIndex]['branch_code']);
                    $data[$headIndex][$datumIndex]['bid'] = $zread->bid;
                    $data[$headIndex][$datumIndex]['created_at'] =
                        ! is_null($zread->created_at)
                            ? Carbon::parse($zread->created_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['updated_at'] =
                        ! is_null($zread->updated_at)
                            ? Carbon::parse($zread->updated_at)->format('Y-m-d H:i:s')
                            : null;
                    $data[$headIndex][$datumIndex]['deleted_at'] =
                        ! is_null($zread->deleted_at)
                            ? Carbon::parse($zread->deleted_at)->format('Y-m-d H:i:s')
                            : null;

                    foreach ($datum->regular_discount as $regularDiscountIndex => $regularDiscount) {
                        $regularDiscount['head_bid'] = $zread->bid;
                        $zreadRegularDiscountData = $regularDiscount;
                        $zreadRegularDiscount = $zread->regularDiscount()->create($zreadRegularDiscountData);

                        foreach ($zreadRegularDiscountData as $zreadRegularDiscountDatumKey => $zreadRegularDiscountDatum) {
                            $data[$headIndex][$datumIndex]['regular_discount'][$regularDiscountIndex][$zreadRegularDiscountDatumKey] = $zreadRegularDiscountDatum;
                        }

                        $data[$headIndex][$datumIndex]['regular_discount'][$regularDiscountIndex]['bid'] = $zreadRegularDiscount->bid;
                        $data[$headIndex][$datumIndex]['regular_discount'][$regularDiscountIndex]['created_at'] =
                            ! is_null($zreadRegularDiscount->created_at)
                                ? Carbon::parse($zreadRegularDiscount->created_at)->format('Y-m-d H:i:s')
                                : null;
                        $data[$headIndex][$datumIndex]['regular_discount'][$regularDiscountIndex]['updated_at'] =
                            ! is_null($zreadRegularDiscount->updated_at)
                                ? Carbon::parse($zreadRegularDiscount->updated_at)->format('Y-m-d H:i:s')
                                : null;
                    }

                    foreach ($datum->tender_detail as $tenderDetailIndex => $tenderDetail) {
                        $tenderDetail['head_bid'] = $zread->bid;
                        $zreadTenderDetailData = $tenderDetail;
                        $zreadTenderDetail = $zread->tenderDetail()->create($zreadTenderDetailData);

                        foreach ($zreadTenderDetailData as $zreadTenderDetailDatumKey => $zreadTenderDetailDatum) {
                            $data[$headIndex][$datumIndex]['tender_detail'][$tenderDetailIndex][$zreadTenderDetailDatumKey] = $zreadTenderDetailDatum;
                        }

                        $data[$headIndex][$datumIndex]['tender_detail'][$tenderDetailIndex]['bid'] = $zreadTenderDetail->bid;
                        $data[$headIndex][$datumIndex]['tender_detail'][$tenderDetailIndex]['created_at'] =
                            ! is_null($zreadTenderDetail->created_at)
                                ? Carbon::parse($zreadTenderDetail->created_at)->format('Y-m-d H:i:s')
                                : null;
                        $data[$headIndex][$datumIndex]['tender_detail'][$tenderDetailIndex]['updated_at'] =
                            ! is_null($zreadTenderDetail->updated_at)
                                ? Carbon::parse($zreadTenderDetail->updated_at)->format('Y-m-d H:i:s')
                                : null;
                    }

                    foreach ($datum->cash_breakdown_detail as $cashBreakdownDetailIndex => $cashBreakdownDetail) {
                        $cashBreakdownDetail['head_bid'] = $zread->bid;
                        $zreadCashBreakdownDetailData = $cashBreakdownDetail;
                        $zreadCashBreakdownDetail = $zread->cashBreakdownDetail()->create($zreadCashBreakdownDetailData);

                        foreach ($zreadCashBreakdownDetailData as $zreadCashBreakdownDetailDatumKey => $zreadCashBreakdownDetailDatum) {
                            $data[$headIndex][$datumIndex]['cash_breakdown_detail'][$cashBreakdownDetailIndex][$zreadCashBreakdownDetailDatumKey] = $zreadCashBreakdownDetailDatum;
                        }

                        $data[$headIndex][$datumIndex]['cash_breakdown_detail'][$cashBreakdownDetailIndex]['bid'] = $zreadCashBreakdownDetail->bid;
                        $data[$headIndex][$datumIndex]['cash_breakdown_detail'][$cashBreakdownDetailIndex]['created_at'] =
                            ! is_null($zreadCashBreakdownDetail->created_at)
                                ? Carbon::parse($zreadCashBreakdownDetail->created_at)->format('Y-m-d H:i:s')
                                : null;
                        $data[$headIndex][$datumIndex]['cash_breakdown_detail'][$cashBreakdownDetailIndex]['updated_at'] =
                            ! is_null($zreadCashBreakdownDetail->updated_at)
                                ? Carbon::parse($zreadCashBreakdownDetail->updated_at)->format('Y-m-d H:i:s')
                                : null;
                    }

                    foreach ($datum->cashier_sales_summary as $cashierSalesSummaryIndex => $cashierSalesSummary) {
                        $cashierSalesSummary['head_bid'] = $zread->bid;
                        $zreadCashierSalesSummaryData = $cashierSalesSummary;
                        $zreadCashierSalesSummary = $zread->cashierSalesSummary()->create($zreadCashierSalesSummaryData);

                        foreach ($zreadCashierSalesSummaryData as $zreadCashierSalesSummaryDatumKey => $zreadCashierSalesSummaryDatum) {
                            $data[$headIndex][$datumIndex]['cashier_sales_summary'][$cashierSalesSummaryIndex][$zreadCashierSalesSummaryDatumKey] = $zreadCashierSalesSummaryDatum;
                        }

                        $data[$headIndex][$datumIndex]['cashier_sales_summary'][$cashierSalesSummaryIndex]['bid'] = $zreadCashierSalesSummary->bid;
                        $data[$headIndex][$datumIndex]['cashier_sales_summary'][$cashierSalesSummaryIndex]['created_at'] =
                            ! is_null($zreadCashierSalesSummary->created_at)
                                ? Carbon::parse($zreadCashierSalesSummary->created_at)->format('Y-m-d H:i:s')
                                : null;
                        $data[$headIndex][$datumIndex]['cashier_sales_summary'][$cashierSalesSummaryIndex]['updated_at'] =
                            ! is_null($zreadCashierSalesSummary->updated_at)
                                ? Carbon::parse($zreadCashierSalesSummary->updated_at)->format('Y-m-d H:i:s')
                                : null;
                    }
                }
            }

            return $data;
        });
    }
}
