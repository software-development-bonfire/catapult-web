<?php

namespace App\Services;

use App\Entities\ApiSetup;
use App\Entities\CDISCashBreakdown;
use App\Entities\CDISCashBreakdownDetail;
use App\Entities\CDISCashDrawer;
use App\Entities\CDISPOSAuditTrail;
use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Entities\CDISTerminalTransactionAddon;
use App\Entities\CDISTerminalTransactionDetail;
use App\Entities\CDISTerminalTransactionDiscount;
use App\Entities\CDISTerminalTransactionPaymentMethod;
use App\Entities\CDISTerminalTransactionProduct;
use App\Entities\CDISZread;
use App\Entities\CDISZreadCashBreakdownDetail;
use App\Entities\CDISZreadCashierSalesSummary;
use App\Entities\CDISZreadRegularDiscount;
use App\Entities\CDISZreadTenderDetail;
use App\Enums\ApiEndpoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class SyncDatabaseService
{
    public function __construct(ErrorLogService $errorLogService)
    {
        $this->errorLogService = $errorLogService;
    }
    public function transaction($TH, $TD, $PR, $PM, $PD = null, $AD = null, $directory, $endpoint)
    {
        // dd($TH, $TD, $PR, $PM, $PD, $AD);
        try {
            return DB::transaction(function() use ($TH, $TD, $PR, $PM, $PD, $AD, $directory, $endpoint) {
                if ($TH) {
                    foreach ($TH as $transaction) {
                        $transactionHead = [
                            'terminal_bid' => CDISTerminal::where('number', $transaction['terminal_number'])->first()->id,
                            'transaction_id' => $transaction['transaction_id'],
                            'date' => Carbon::parse($transaction['date'])->format('Y-m-d H:i:s'),
                            'amount' => $transaction['amount'],
                            'transaction_type' => $transaction['transaction_type'],
                            'is_zread' => $transaction['is_zread'],
                            'type' => $transaction['type'],
                            'status' => $transaction['status'],
                            'gross' => $transaction['gross'],
                            'total_quantity' => $transaction['total_quantity'],
                            'total_free_items_amount' => $transaction['total_free_items_amount'],
                            'total_local_tax_amount' => $transaction['total_local_tax_amount'],
                            'total_tax_amount' => $transaction['total_tax_amount'],
                            'total_discount_amount' => $transaction['total_discount_amount'],
                            'total_vat_exempt_amount' => $transaction['total_vat_exempt_amount'],
                            'total_vat_deduct_amount' => $transaction['total_vat_deduct_amount'],
                            'total_vatable_sales' => $transaction['total_vatable_sales'],
                            'total_zero_rated_sales' => $transaction['total_zero_rated_sales'],
                            'log_date' => Carbon::parse($transaction['log_date'])->format('Y-m-d H:i:s'),
                            'order_number' => $transaction['order_number'],
                            'table_number' => $transaction['table_number'],
                            'guest_count' => $transaction['guest_count'],
                        ];
            
                        $head = CDISTerminalTransaction::create($transactionHead);
                    }
                }
        
                if ($TD) {
                    foreach ($TD as $transaction_detail) {
                        $transactionDetail = [
                            'transaction_head_bid' => $head->bid,
                            'split_number' => $transaction_detail['split_number'],
                            'or_number' => $transaction_detail['number'],
                            'total' => $transaction_detail['total'],
                            'discount_amount' => $transaction_detail['discount_amount'],
                            'free_items_amount' => $transaction_detail['free_items_amount'],
                            'vat_deduct_amount' => $transaction_detail['vat_deduct_amount'],
                            'vat_exempt_amount' => $transaction_detail['vat_exempt_amount'],
                            'original_amount' => $transaction_detail['original_amount'],
                            'quantity' => $transaction_detail['quantity'],
                            'local_tax_amount' => $transaction_detail['local_tax_amount'],
                            'tax_amount' => $transaction_detail['tax_amount'],
                            'service_charge' => $transaction_detail['service_charge'],
                            'vatable_sales' => $transaction_detail['vatable_sales'],
                            'zero_rated_sales' => $transaction_detail['zero_rated_sales'],
                            'total_tender' => $transaction_detail['total_tender'],
                            'cashier_bid' => $transaction_detail['cashier_id'],
                            'cashier_name' => $transaction_detail['cashier_name'],
                            'customer_bid' => $transaction_detail['customer_id'] ?? null,
                            'customer_type' => $transaction_detail['customer_type'] ?? 0,
                            'customer_name' => $transaction_detail['customer_name'] ?? null,
                            'customer_address' => $transaction_detail['customer_address'] ?? null,
                            'eligible_amount_to_earn_points' => $transaction_detail['eligible_amount_to_earn_points'],
                        ];
            
                        $detail = CDISTerminalTransactionDetail::create($transactionDetail);
                    }
                }

                if ($PM) {
                    foreach ($PM as $payment_method) {
                        $pm = [
                            'transaction_detail_bid' => $detail->bid,
                            "title" => $payment_method['payment_method_title'],
                            "total" => $payment_method['payment_method_total'],
                            "acount_number" => $payment_method['payment_method_account_number']
                        ];
                        $payment_method = CDISTerminalTransactionPaymentMethod::create($pm);
                    }
                }
        
                if ($PR) {
                    foreach ($PR as $product) {
                        $pr = [
                            'transaction_detail_bid' => $detail->bid,
                            "product_bid" => $product['product_id'],
                            "name" => $product['product_name'],
                            "description" => $product['product_description'],
                            "long_description" => $product['product_long_description'],
                            "menu_code" => $product['product_menu_code'],
                            "category_bid" => $product['category_id'],
                            "category_name" => $product['category_name'],
                            "quantity" => $product['product_quantity'],
                            "tax_percentage" => $product['product_tax_percentage'],
                            "order_type_id" => $product['order_type_id'],
                            "order_type_name" => $product['order_type_name'],
                            "is_free" => $product['product_is_free'],
                            "is_vatable" => $product['product_is_vatable'],
                            "price" => $product['product_price'],
                            "total_addon" => $product['product_total_addon'],
                            "total_amount" => $product['product_total_amount'],
                            "total_discount" => $product['product_total_discount'],
                            "vatable_sales" => $product['product_vatable_sales'],
                            "zero_rated_sales" => $product['product_zero_rated_sales'],
                            "amount_discount" => $product['product_amount_discount'],
                            "_tax" => $product['product_tax'],
                            "vat_deduct" => $product['product_vat_deduct'],
                            "vat_exempt" => $product['product_vat_exempt'],
                            "split_number" => $product['product_split_number'],
                        ];
        
                        $saveProduct = CDISTerminalTransactionProduct::create($pr);

                        if ($AD) {
                            foreach ($AD as $addon) {
                                if ($product['product_id'] === $addon['addon_bid']) {
                                    $ad = [
                                        'transaction_product_bid' => $saveProduct['bid'],                                        
                                        'name' => $addon['name'],
                                        'description' => toSafeValue($addon['addon_description'], null),
                                        'long_description' => toSafeValue($addon['addon_long_description'], null),
                                        'menu_code' => toSafeValue($addon['addon_menu_code'], null),
                                        'category_bid' => toSafeValue($addon['addon_category_bid'], null),
                                        'category_name' => toSafeValue($addon['addon_category_name'], null),
                                        'quantity' => toSafeValue($addon['addon_quantity'], 0.000000),
                                        'tax_percentage' => toSafeValue($addon['addon_tax_percentage'], 0.000000),
                                        'order_type_id' => toSafeValue($addon['addon_order_type_id'], null),
                                        'order_type_name' => toSafeValue($addon['addon_order_type_name'], null),
                                        'is_free' => toSafeValue($addon['addon_is_free'], 0),
                                        'is_vatable' => toSafeValue($addon['addon_is_vatable'], 1),
                                        'original_price' => toSafeValue($addon['addon_original_price'], 0.000000),
                                        'price' => toSafeValue($addon['addon_price'], 0.000000),
                                        'total_amount' => toSafeValue($addon['addon_total_amount'], 0.000000),
                                        'vatable_sales' => toSafeValue($addon['addon_vatable_sales'], 0.000000),
                                        'zero_rated_sales' => toSafeValue($addon['addon_zero_rated_sales'], 0.000000),
                                        'amount_discount' => toSafeValue($addon['addon_amount_discount'], 0.000000),
                                        'tax' => toSafeValue($addon['addon_tax'], 0.000000),
                                        'vat_deduct' => toSafeValue($addon['addon_vat_deduct'], 0.000000),
                                        'vat_exempt' => toSafeValue($addon['addon_vat_exempt'], 0.000000),
                                        'split_number' => toSafeValue($addon['addon_split_number'], null),
                                        'remarks' => toSafeValue($addon['addon_remarks'], null),
                                        'usage_type' => toSafeValue($addon['addon_usage_type'], \App\Enums\UsageType::ADDON),
                                        'supervisor_bid' => toSafeValue($addon['addon_supervisor_bid'], null),
                                        'supervisor_name' => toSafeValue($addon['addon_supervisor_name'], null),
                                    ];
                                    CDISTerminalTransactionAddon::create($ad);
                                }
                            }
                        }

                        if ($PD) {
                            foreach ($PD as $discount) {
                                if ($product['product_id'] === $discount['discount_id']) {
                                    $pd = [
                                        'transaction_product_bid' => $saveProduct['bid'],
                                        'discount_bid' => $discount['discount_id'],
                                        'title' => $discount['discount_title'],
                                        'total' => $discount['discount_total'],
                                        'amount_discount' => $discount['discount_amount_discount'],
                                        'vat_deduct' => $discount['discount_vat_deduct'],
                                        'vat_exempt' => $discount['discount_vat_exempt'],
                                        'mandated' => $discount['discount_mandated'],
                                    ];
                                    
                                    CDISTerminalTransactionDiscount::create($pd);
                                }
                            }
                        }
                    }
                }

                if ($head && $detail && $payment_method && $product) {
                    return true;
                } else {
                    return false;
                }
            });
        } catch (\Throwable $th) {
            $data = [
                'endpoint' => ApiEndpoint::TRANSACTION,
                'filename' => $directory,
                'status' => Lang::get('error.failed_conversion'),
                'sheet' => 'N/A',
                'error_type' => Lang::get('error.failed_to_insert_the_data'),
                'description' => $th->getMessage(),
            ];

            $this->errorLogService->store($data);
            $this->errorLogService->moveFailedConversion($directory, $endpoint);

            return false;
        }
    }

    public function zread($ZCB, $ZCS, $ZH, $ZRD, $ZTD, $directory, $endpoint)
    {
        try {
            return DB::transaction(function () use ($ZCB, $ZCS, $ZH, $ZRD, $ZTD, $directory, $endpoint) {
                if ($ZH) {
                    foreach ($ZH as $zread) {
                        $zh = [
                            'terminal_bid' => CDISTerminal::where('number', $zread['terminal_number'])->first()->id,
                            "day_end_report_number" => $zread['day_end_report_number'],
                            "administrator" => $zread['administrator'],
                            "cashier" => $zread['cashier'],
                            "log_date" => Carbon::parse($zread['log_date'])->format('Y-m-d'),
                            "date_time" => Carbon::parse($zread['date_time'])->format('y-m-d H:i:s'),
                            "terminal_bid" => (int) $zread['terminal_number'],
                            // "branch_code" => $zread['branch_code'],
                            "guest_count" => $zread['guest_count'],
                            "gross_sales_amount" => $zread['gross_sales_amount'],
                            "transaction_count" => $zread['transaction_count'],
                            "mandated_discount_transaction_count" => $zread['mandated_discount_transaction_count'],
                            "total_regular_discount_count" => $zread['total_regular_discount_count'],
                            "total_regular_discount_amount" => $zread['total_regular_discount_amount'],
                            "senior_transaction_count" => $zread['senior_transaction_count'],
                            "senior_discount_amount" => $zread['senior_discount_amount'],
                            "sc_vat_deduction_amount" => $zread['sc_vat_deduction_amount'],
                            "pwd_transaction_count" => $zread['pwd_transaction_count'],
                            "pwd_transaction_amount" => $zread['pwd_transaction_amount'],
                            "pwd_vat_deduction" => $zread['pwd_vat_deduction'],
                            "diplomat_transaction_count" => $zread['diplomat_transaction_count'],
                            "diplomat_vat_deduction" => $zread['diplomat_vat_deduction'],
                            "vatable_sales" => $zread['vatable_sales'],
                            "vat_amount" => $zread['vat_amount'],
                            "vat_exempt_sales" => $zread['vat_exempt_sales'],
                            "less_mandated_vat_and_discount" => $zread['less_mandated_vat_and_discount'],
                            "net_of_vat_exempt" => $zread['net_of_vat_exempt'],
                            "zero_rated_sales" => $zread['zero_rated_sales'],
                            "non_vat_sales" => $zread['non_vat_sales'],
                            "subtotal" => $zread['subtotal'],
                            "service_charge" => $zread['service_charge'],
                            "net_total" => $zread['net_total'],
                            "beginning_or" => $zread['beginning_or'],
                            "ending_or" => $zread['ending_or'],
                            "no_sales_transaction_count" => $zread['no_sales_transaction_count'],
                            "void_transactions_count" => $zread['void_transactions_count'],
                            "void_transactions_amount" => $zread['void_transactions_amount'],
                            "void_items_count" => $zread['void_items_count'],
                            "void_items_amount" => $zread['void_items_amount'],
                            "refunds_count" => $zread['refunds_count'],
                            "refunds_amount" => $zread['refunds_amount'],
                            "total_tenders_count" => $zread['total_tenders_count'],
                            "total_tenders_amount" => $zread['total_tenders_amount'],
                            "add_initial_cash_amount" => $zread['add_initial_cash_amount'],
                            "less_withdrawals_amount" => $zread['less_withdrawals_amount'],
                            "add_cash_returns_amount" => $zread['add_cash_returns_amount'],
                            "total_drawer_amount" => $zread['total_drawer_amount'],
                            "total_cash_breakdown_amount" => $zread['total_cash_breakdown_amount'],
                            "short_over_amount" => $zread['short_over_amount'],
                            "total_cashier_sales_amount" => $zread['total_cashier_sales_amount'],
                            "beginning_balance" => $zread['beginning_balance'],
                            "ending_balance" => $zread['ending_balance'],
                        ];
        
                        $head = CDISZread::create($zh);
                    }
                }
        
                if ($ZCB) {
                    foreach ($ZCB as $cashBreakdown) {
                        $zcb = [
                            'head_bid' => isset($head->bid) ? $head->bid : "",
                            'denomination' => $cashBreakdown['cash_breakdown_detail_denomination'],
                            'quantity' => $cashBreakdown['cash_breakdown_count'],
                            'amount' => $cashBreakdown['cash_breakdown_amount'],
                        ];
        
                        $cash_breakdown = CDISZreadCashBreakdownDetail::create($zcb);
                    }
                }
        
                if ($ZCS) {
                    foreach ($ZCS as $cashierSummary) {
                        $zcs = [
                            'head_bid' => isset($head->bid) ? $head->bid : "",
                            'name' => $cashierSummary['cashier_sales_summary_name'],
                            'amount' => $cashierSummary['cashier_sales_summary_amount'],
                        ];
        
                        $sales_summary = CDISZreadCashierSalesSummary::create($zcs);
                    }
                }
        
                if ($ZRD) {
                    foreach ($ZRD as $regularDiscount) {
                        $zrd = [
                            'head_bid' => isset($head->bid) ? $head->bid : "",
                            'name' => $regularDiscount['regular_discount_name'],
                            'count' => $regularDiscount['regular_discount_count'],
                            'amount' => $regularDiscount['regular_discount_amount'],
                        ];
        
                        $regular_discount = CDISZreadRegularDiscount::create($zrd);
                    }
                }
        
                if ($ZTD) {
                    foreach ($ZTD as $tenderDetail) {
                        $ztd = [
                            'head_bid' => $head->bid,
                            'name' => $tenderDetail['tender_detail_name'],
                            'count' => $tenderDetail['tender_detail_count'],
                            'amount' => $tenderDetail['tender_detail_amount'],
                        ];
        
                        $tender_detail = CDISZreadTenderDetail::create($ztd);
                    }
                }
    
                if ($head && $cash_breakdown && $sales_summary && $regular_discount && $tender_detail) {
                    return true;
                } else {
                    return false;
                }
            });
        } catch (\Throwable $th) {
            $data = [
                'endpoint' => ApiEndpoint::ZREAD,
                'filename' => $directory,
                'status' => Lang::get('error.failed_conversion'),
                'sheet' => 'N/A',
                'error_type' => Lang::get('error.failed_to_insert_the_data'),
                'description' => $th->getMessage(),
            ];

            $this->errorLogService->store($data);
            $this->errorLogService->moveFailedConversion($directory, $endpoint);

            return false;
        }
    }

    public function auditTrail($AT, $directory, $endpoint)
    {
        try {
            return DB::transaction(function () use ($AT, $directory, $endpoint) {
                if ($AT) {
                    foreach ($AT as $auditTrail) {
                        $at = [
                            'terminal_bid' => $auditTrail['terminal_number'],
                            'date' => Carbon::parse($auditTrail['date'])->format('Y-m-d H:i:s'),
                            'application' => $auditTrail['application'],
                            'cashier' => $auditTrail['cashier'],
                            'supervisor' => $auditTrail['supervisor'],
                            'job' => $auditTrail['job'],
                            'transaction_no' => $auditTrail['transaction_no'],
                            'receipt_no' => $auditTrail['receipt_no'],
                            'remarks' => $auditTrail['remarks']
                        ];
        
                        $data = CDISPOSAuditTrail::create($at);
                    }
                }
    
                if ($data) {
                    return true;
                } else {
                    return false;
                }
            });
        } catch (\Throwable $th) {
            $data = [
                'endpoint' => ApiEndpoint::AUDIT_TRAIL,
                'filename' => $directory,
                'status' => Lang::get('error.failed_conversion'),
                'sheet' => 'N/A',
                'error_type' => Lang::get('error.failed_to_insert_the_data'),
                'description' => $th->getMessage(),
            ];

            $this->errorLogService->store($data);
            $this->errorLogService->moveFailedConversion($directory, $endpoint);

            return false;
        }
    }

    public function cashBreakdown($CH, $CD, $directory, $endpoint)
    {
        try {
            return DB::transaction(function () use ($CH, $CD, $directory, $endpoint) {
                if ($CH) {
                    foreach ($CH as $cashBreakdown) {
                        $ch = [
                            'terminal_bid' => $cashBreakdown['terminal_number'],
                            'cashier_bid' => $cashBreakdown['cashier_id'],
                            'cashier_name' => $cashBreakdown['cashier_name'],
                            'date' => Carbon::parse($cashBreakdown['date'])->format('Y-m-d H:i:s'),
                            'approver_bid' => $cashBreakdown['approver_id'],
                            'approver_name' => $cashBreakdown['approver_name'],
                            'approved_date' => Carbon::parse($cashBreakdown['approved_date'])->format('Y-m-d H:i:s'),
                            'remarks' => $cashBreakdown['remarks'],
                        ];
        
                        $head = CDISCashBreakdown::create($ch);
                    }
                }
        
                if ($CD) {
                    foreach ($CD as $cashBreakdownDetail) {
                        $cd = [
                            'head_bid' => isset($head->bid) ? $head->bid : "",
                            "denomination" => $cashBreakdownDetail['detail_denomination'],
                            "quantity" => $cashBreakdownDetail['detail_quantity'],
                            "amount" => $cashBreakdownDetail['detail_amount']
                        ];
        
                        $detail = CDISCashBreakdownDetail::create($cd);
                    }
                }
    
                if ($head && $detail) {
                    return true;
                } else {
                    return false;
                }
            });
        } catch (\Throwable $th) {
            $data = [
                'endpoint' => ApiEndpoint::CASH_BREAKDOWN,
                'filename' => $directory,
                'status' => Lang::get('error.failed_conversion'),
                'sheet' => 'N/A',
                'error_type' => Lang::get('error.failed_to_insert_the_data'),
                'description' => $th->getMessage(),
            ];

            $this->errorLogService->store($data);
            $this->errorLogService->moveFailedConversion($directory, $endpoint);

            return false;
        }
    }

    public function cashDrawer($DR, $directory, $endpoint)
    {
        try {
            return DB::transaction(function () use ($DR, $directory, $endpoint) {
                if ($DR) {
                    foreach ($DR as $cashDrawer) {
                        $dr = [
                            'terminal_bid' => (int) $cashDrawer['terminal_number'],
                            'cashier_bid' => (int) $cashDrawer['cashier_id'],
                            'cashier_name' => $cashDrawer['cashier_name'],
                            'amount' => $cashDrawer['amount'],
                            'date' => Carbon::parse($cashDrawer['date'])->format('Y-m-d H:i:s'),
                            'approver_bid' => $cashDrawer['approver_id'],
                            'approver_name' => $cashDrawer['approver_name'],
                            'approved_date' => Carbon::parse($cashDrawer['approved_date'])->format('Y-m-d H:i:s'),
                            'type' => $cashDrawer['type'],
                            'remarks' => $cashDrawer['remarks'],
                        ];
        
                        $data = CDISCashDrawer::create($dr);
                    }
                }
    
                if ($data) {
                    return true;
                } else {
                    return false;
                }
            });
        } catch (\Throwable $th) {
            $data = [
                'endpoint' => ApiEndpoint::CASH_DRAWER,
                'filename' => $directory,
                'status' => Lang::get('error.failed_conversion'),
                'sheet' => 'N/A',
                'error_type' => Lang::get('error.failed_to_insert_the_data'),
                'description' => $th->getMessage(),
            ];

            $this->errorLogService->store($data);
            $this->errorLogService->moveFailedConversion($directory, $endpoint);

            return false;
        }
    }
}