<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FileNameIdentifier extends Enum
{
    const TH = "Transaction Head";
    const TD = "Transaction Detail";
    const TDD = "Transaction Detail Discount";
    const PR = "Product";
    const PM = "Payment Method";
    const AD = "Add on";
    const PD = "Product Discount";
    const ZCB = "Cash Breakdown";
    const ZCS = "Cashier Summary";
    const ZH = "Zread Head";
    const ZTD = "Tender Details";
    const ZRD = "Regular Discount";
    const AT = "Audit Trail";
    const CH = "Cash Breakdown Head";
    const CD = "Cash Breakdown Detail";
    const DR = "Cash Drawer";
}
