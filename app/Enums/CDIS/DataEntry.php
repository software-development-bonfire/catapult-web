<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Enum;

final class DataEntry extends Enum
{
    const TRANSACTION = "Transaction";
    const ZREAD = "Zread";
    const AUDIT_TRAIL = "Audit Trail";
    const CASH_BREAKDOWN = "Cash Breakdown";
    const CASH_DRAWER = "Cash Drawer";
    const VENDOR = "Vendor";
    const VENDOR_BRANCH = "Vendor Branch";
    const PRODUCT = "Product";
    const UOM = "Unit Of Measurement";
    const CATEGORY = "Category";
    const BRAND = "Brand";
    const BRANCH = "Branch";
    const PRODUCT_STRUCTURE = "Product Structure";
    const PRODUCT_STRUCTURE_DETAIL = "Product Structure Detail";
    const PRODUCT_PRICING_TYPE = "Product Pricing Type";
    const PRODUCT_UOM_PACKAGING = "Product UOM Packaging";
    const PRODUCT_BRANCH_PRICE = "Product Branch Price";
    const PRODUCT_BRANCH_AVAILABILITY = "Product Branch Availability";
    const PACKAGING_VENDOR = "Packaging Vendor";
    const PACKAGING_VENDOR_BRANCH_COST = "Packaging Vendor Branch Cost";
    const PRODUCT_ADD_ON = "Product Add On";
    const PRODUCT_ADD_ON_DETAIL = "Product Add On Detail";
}
