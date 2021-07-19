<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Acronym extends Enum
{
    const TRANSACTION_HEAD = "TH";
    const TRANSACTION_DETAIL = "TD";
    const PRODUCT = "PR";
    const PAYMENT_METHOD = "PM";
    const ADDON = "AD";
    const PRODUCT_DISCOUNT = "PD";
    const CASH_BREAKDOWN = "ZCB";
    const CASHIER_SUMMARY = "ZCS";
    const ZREAD_HEAD = "ZH";
    const REGULAR_DISCOUNT = "ZRD";
    const TENDER_DETAILS = "ZTD";
    const AUDIT_TRAIL = "AT";
    const CASH_BREACKDOWN_HEAD = "CH";
    const CASH_BREACKDOWN_DETAIL = "CD";
    const CASH_DRAWER = "DR";

}
