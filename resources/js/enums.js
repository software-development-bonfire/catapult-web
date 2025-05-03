window.STATUS = {
    ACTIVE: 1,
    INACTIVE: 0
}

window.MAPPING_TYPE = {
    CDIS_TO_POS: 1,
    POS_TO_CDIS: 2
}

window.SYNCING_TYPE = {
    CATAPULT_TO_CDIS: 1,
    CDIS_TO_CATAPULT: 2
}

window.API_ENDPOINT = {
    CDIS: {
        PRODUCT: 1,
        BRAND: 2,
        CATEGORY: 3,
        VENDOR: 4,
        UOM: 5,
    },
    POS: {
        TRANSACTION: 1,
        ZREAD: 2,
        AUDIT_TRAIL: 3,
        CASH_BREAKDOWN: 4,
        CASH_DRAWER: 5,
    }
}

window.REPORT_FILE_TYPE = {
    SALES_TRANSACTIONS: 1,
    JOURNAL_REPORTS: 2,
    OTHER_REPORTS: 3,
    Z_READING: 4,
}

window.POS = {
    SIRIUS_POS: 1,
    PDA: 2,
    KIOSK: 3,
    QR_MOBILE: 4,
    KDS: 5,
    QUEUEING: 6,
    ECOMMERCE: 7,
}