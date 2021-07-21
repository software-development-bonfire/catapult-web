<?php

namespace App\Services;


class CatapultToJsonFormatService
{

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $TH
     * @param Array  $TD
     * @param Array  $PR
     * @param Array  $PM
     * @param Array  $PD
     * @param Array  $AD
     * @return \Illuminate\Http\Response
     */
    public function transaction($TH, $TD, $PR, $PM, $PD, $AD)
    {
        return (object) [ "transaction" => collect($TH)->map(function ($data) use ($TD, $PM, $PR, $PD, $AD) {
            return [
                "branch_code" => $data['branch_code'],
                "terminal_number" => $data['terminal_number'],
                "transaction_id" => $data['transaction_id'],
                "date" => $data['date'],
                "amount" => $data['amount'],
                "transaction_type" => $data['transaction_type'],
                "is_zread" => $data['is_zread'],
                "status" => $data['status'],
                "type" => $data['type'],
                "gross" => $data['gross'],
                "total_quantity" => $data['total_quantity'],
                "total_free_items_amount" => $data['total_free_items_amount'] ,
                "total_local_tax_amount" => $data['total_local_tax_amount'] ,
                "total_tax_amount" => $data['total_tax_amount'] ,
                "total_discount_amount" => $data['total_discount_amount'],
                "total_vat_exempt_amount" => $data['total_vat_exempt_amount'],
                "total_vat_deduct_amount" => $data['total_vat_deduct_amount'],
                "total_vatable_sales" => $data['total_vatable_sales'],
                "total_zero_rated_sales" => $data['total_zero_rated_sales'],
                "log_date" => $data['log_date'],
                "order_number" => $data['order_number'],
                "table_number" => $data['table_number'],
                "guest_count" => $data['guest_count'],
                "official_receipt" => (object) [
                    "split_number" => $TD[0]['split_number'],
                    "number" => $TD[0]['number'],
                    'total' => $TD[0]['total'],
                    "discount_amount" => $TD[0]['discount_amount'],
                    "free_items_amount" => $TD[0]['free_items_amount'],
                    "vat_deduct_amount" => $TD[0]['vat_deduct_amount'],
                    "vat_exempt_amount" => $TD[0]['vat_exempt_amount'],
                    "original_amount" => $TD[0]['original_amount'],
                    "quantity" => $TD[0]['quantity'],
                    "local_tax_amount" => $TD[0]['local_tax_amount'],
                    "tax_amount" => $TD[0]['tax_amount'],
                    "service_charge" => $TD[0]['service_charge'],
                    "vatable_sales" => $TD[0]['vatable_sales'],
                    "zero_rated_sales" => $TD[0]['zero_rated_sales'],
                    "eligible_amount_to_earn_points" => $TD[0]['eligible_amount_to_earn_points'],
                    "total_tender" => $TD[0]['total_tender'],
                    "cashier" => (object) [
                        'id' => $TD[0]['cashier_id'],
                        'name' => $TD[0]['cashier_name']
                    ],
                    "customer" => (object) [
                        'id' => $TD[0]['customer_id'],
                        'type' => $TD[0]['customer_type'],
                        'name' => $TD[0]['customer_name'],
                        'address' => $TD[0]['customer_address']
                    ],
                    "payment_method" => (object) [
                        'title' =>  $PM[0]['payment_method_title'],
                        'total' => $PM[0]['payment_method_total'],
                        'account_number' => $PM[0]['payment_method_account_number'],
                    ],
                    "product" => [
                        collect($PR)->map(function ($product) use ($PD, $AD) {
                            return [
                                'id' => $product['product_id'],
                                'menu_code' => $product['product_menu_code'],
                                'name' => $product['product_name'],
                                'description' => $product['product_description'],
                                'long_description' => $product['product_long_description'],
                                'quantity' => $product['product_quantity'],
                                'tax_percentage' => $product['product_tax_percentage'],
                                'is_free' => $product['product_is_free'],
                                'is_vatable' => $product['product_is_vatable'],
                                'original_price' => $product['product_original_price'],
                                'total_addon' => $product['product_total_addon'],
                                'total_amount' => $product['product_total_amount'],
                                'amount_discount' => $product['product_amount_discount'],
                                'vatable_sales' => $product['product_vatable_sales'],
                                'zero_rated_sales' => $product['product_zero_rated_sales'],
                                'tax' => $product['product_tax'],
                                'vat_exempt' => $product['product_vat_exempt'],
                                'vat_deduct' => $product['product_vat_deduct'],
                                'split_number' => $product['product_vat_deduct'],
                                'order_type' => [
                                    'id' => $product['order_type_id'],
                                    'name' => $product['order_type_name']
                                ],
                                'category' => [
                                    'id' => $product['category_id'],
                                    'name' => $product['category_name']
                                ],
                                'addon' => [
                                    collect($AD)->map(function ($addon) {
                                        return [
                                            'name' => $addon['addon_name'],
                                            'quantity' => $addon['addon_quantity'],
                                            'original_price' => $addon['addon_original_price'],
                                            'price' => $addon['addon_price'],
                                            'total_amount' => $addon['addon_total_amount'],
                                        ];
                                    })
                                ],
                                'discount' => collect($PD)->where('product_bid', $product['product_id'])
                                ->map(function ($discount) use ($product) {
                                        return [
                                            'id' => isset($discount['discount_id']) ? $discount['discount_id'] : 0,
                                            'mandated' => isset($discount['discount_mandated']) ? $discount['discount_mandated'] : 0,
                                            'title' => isset($discount['discount_title']) ? $discount['discount_title'] : '""',
                                            'total' => isset($discount['discount_total']) ? $discount['discount_total'] : '""',
                                            'amount_discount' => isset($discount['discount_amount_discount']) ? $discount['discount_amount_discount'] : 0,
                                            'vat_deduct' => isset($discount['discount_vat_deduct']) ? $discount['discount_vat_deduct'] : 0,
                                            'vat_exempt' => isset($discount['discount_vat_exempt']) ? $discount['discount_vat_exempt'] : 0,
                                        ];
                                    }
                                ),
                                'price_override_details' => [
                                    'price' => $product['price_override_details_price'],
                                    'approved_by' => $product['price_override_details_approved_by'],
                                    'approved_date' => $product['price_override_details_approved_date']
                                ]
                            ];
                        })
                    ]
                ],
            ];
        })];
    }

    public function zread($ZCB, $ZCS, $ZH, $ZRD, $ZTD)
    {
        return (object) ["zread" => collect($ZH)->map(function ($data) use ($ZCB, $ZCS, $ZRD, $ZTD) {
            return [
                'day_end_report_number' => $data['day_end_report_number'],
                'administrator' => $data['administrator'],
                'cashier' => $data['cashier'],
                'log_date' => $data['log_date'],
                'date_time' => $data['date_time'],
                'terminal_number' => $data['terminal_number'],
                'branch_code' => $data['branch_code'],
                'guest_count' => $data['guest_count'],
                'gross_sales_amount' => $data['gross_sales_amount'],
                'transaction_count' => $data['transaction_count'],
                'mandated_discount_transaction_count' => $data['mandated_discount_transaction_count'],
                'regular_discount' => [
                    collect($ZRD)->map(function ($RD) {
                        return [
                            'name' => $RD['regular_discount_name'],
                            'count' => $RD['regular_discount_count'],
                            'amount' => $RD['regular_discount_amount'],
                        ];
                    })
                ],
                'total_regular_discount_count' => $data['total_regular_discount_count'],
                'total_regular_discount_amount' => $data['total_regular_discount_amount'],
                'senior_transaction_count' => $data['senior_transaction_count'],
                'senior_discount_amount' => $data['senior_discount_amount'],
                'sc_vat_deduction_amount' => $data['sc_vat_deduction_amount'],
                'pwd_transaction_count' => $data['pwd_transaction_count'],
                'pwd_transaction_amount' => $data['pwd_transaction_amount'],
                'pwd_vat_deduction' => $data['pwd_vat_deduction'],
                'diplomat_transaction_count' => $data['diplomat_transaction_count'],
                'diplomat_vat_deduction' => $data['diplomat_vat_deduction'],
                'vatable_sales' => $data['vatable_sales'],
                'vat_amount' => $data['vat_amount'],
                'vat_exempt_sales' => $data['vat_exempt_sales'],
                'less_mandated_vat_and_discount' => $data['less_mandated_vat_and_discount'],
                'net_of_vat_exempt' => $data['net_of_vat_exempt'],
                'zero_rated_sales' => $data['zero_rated_sales'],
                'non_vat_sales' => $data['non_vat_sales'],
                'subtotal' => $data['subtotal'],
                'service_charge' => $data['service_charge'],
                'net_total' => $data['net_total'],
                'beginning_or' => $data['beginning_or'],
                'ending_or' => $data['ending_or'],
                'no_sales_transaction_count' => $data['no_sales_transaction_count'],
                'void_transactions_count' => $data['void_transactions_count'],
                'void_transactions_amount' => $data['void_transactions_amount'],
                'void_items_count' => $data['void_items_count'],
                'void_items_amount' => $data['void_items_amount'],
                'refunds_count' => $data['refunds_count'],
                'refunds_amount' => $data['refunds_amount'],
                'tender_detail' => [
                    collect($ZTD)->map(function ($TD) {
                        return [
                            'name' => $TD['tender_detail_name'],
                            'count' => $TD['tender_detail_count'],
                            'amount' => $TD['tender_detail_amount'],
                        ];
                    })
                ],
                'total_tenders_count' => $data['total_tenders_count'],
                'total_tenders_amount' => $data['total_tenders_amount'],
                'add_initial_cash_amount' => $data['add_initial_cash_amount'],
                'less_withdrawals_amount' => $data['less_withdrawals_amount'],
                'add_cash_returns_amount' => $data['add_cash_returns_amount'],
                'total_drawer_amount' => $data['total_drawer_amount'],
                'cash_breakdown_detail' => [
                    collect($ZCB)->map(function ($CB) {
                        return [
                            'denomination' => $CB['cash_breakdown_detail_denomination'],
                            'count' => $CB['cash_breakdown_detail_count'],
                            'amount' => $CB['cash_breakdown_detail_amount'],
                        ];
                    })
                ],
                'total_cash_breakdown_amount' => $data['total_cash_breakdown_amount'],
                'short_over_amount' => $data['short_over_amount'],
                'cashier_sales_summary' => [
                    collect($ZCS)->map(function ($CS) {
                        return [
                            'name' => $CS['cashier_sales_summary_name'],
                            'amount' => $CS['cashier_sales_summary_amount'],
                        ];
                    })
                ],
                'total_cashier_sales_amount' => $data['total_cashier_sales_amount'],
                'beginning_balance' => $data['beginning_balance'],
                'ending_balance' => $data['ending_balance'],
            ];
        })];
    }
    public function auditTrail($AT)
    {
        return (object) ['audit_trail' =>collect($AT)->map(function ($data) {
            return [
                'branch_code' => $data['branch_code'],
                'terminal_number' => $data['terminal_number'],
                'date' => $data['date'],
                'application' => $data['application'],
                'cashier' => $data['cashier'],
                'supervisor' => $data['supervisor'],
                'job' => $data['job'],
                'transaction_no' => $data['transaction_no'],
                'receipt_no' => $data['receipt_no'],
                'remarks' => $data['remarks'],
            ];
        })];
    }

    public function cashBreakdown($CH, $CD)
    {
        return (object) ['cash_breakdown' => collect($CH)->map(function ($data) use ($CD) {
            return [
                'branch_code' => $data['branch_code'],
                'terminal_number' => $data['terminal_number'],
                'cashier_id' => $data['cashier_id'],
                'cashier_id' => $data['cashier_id'],
                'cashier_name' => $data['cashier_name'],
                'date' => $data['date'],
                'approver_id' => $data['approver_id'],
                'approver_name' => $data['approver_name'],
                'approved_date' => $data['approved_date'],
                'remarks' => $data['remarks'],
                'detail' => [
                    collect($CD)->map(function ($value) {
                        return [
                            'denomination' => $value['detail_denomination'],
                            'quantity' => $value['detail_quantity'],
                            'amount' => $value['detail_amount']
                        ];
                    })
                ]
            ];
        })];
    }

    public function cashDrawer($CD)
    {
        return (object) ['cash_drawer' => collect($CD)->map(function ($data) {
            return [
                'branch_code' => $data['branch_code'],
                'terminal_number' => $data['terminal_number'],
                'cashier_id' => $data['cashier_id'],
                'cashier_name' => $data['cashier_name'],
                'amount' => $data['amount'],
                'date' => $data['date'],
                'approver_id' => $data['approver_id'],
                'approver_name' => $data['approver_name'],
                'approved_date' => $data['approved_date'],
                'type' => $data['type'],
                'remarks' => $data['remarks']
            ];
        })];
    }
}


