<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Directory extends Enum
{
    const POS_TO_CDIS = "POS to CDIS";

    const FOR_CONVERSION_TRANSACTION_TO_CONVERT = '/POS to CDIS/For Conversion/Transaction/To Convert';
    const FOR_CONVERSION_TRANSACTION_PROCESSED = '/POS to CDIS/For Conversion/Transaction/Processed';
    const FOR_CONVERSION_TRANSACTION_FAILED = "/POS to CDIS/For Conversion/Transaction/Failed Conversion";
    const CONVERTED_TRANSACTION_TO_SYNC = "/POS to CDIS/Converted/Transaction/To Sync";
    const CONVERTED_TRANSACTION_SYNCED = "/POS to CDIS/Converted/Transaction/Synced";
    const CONVERTED_TRANSACTION_FAILED_SYNC = "/POS to CDIS/Converted/Transaction/Failed Sync";
    
    const FOR_CONVERSION_ZREAD_TO_CONVERT = '/POS to CDIS/For Conversion/Zread/To Convert';
    const FOR_CONVERSION_ZREAD_PROCESSED = '/POS to CDIS/For Conversion/Zread/Processed';
    const FOR_CONVERSION_ZREAD_FAILED = "/POS to CDIS/For Conversion/Zread/Failed Conversion";
    const CONVERTED_ZREAD_TO_SYNC = "/POS to CDIS/Converted/Zread/To Sync";
    const CONVERTED_ZREAD_SYNCED = "/POS to CDIS/Converted/Zread/Synced";
    const CONVERTED_ZREAD_FAILED_SYNC = "/POS to CDIS/Converted/Zread/Failed Sync";

    const FOR_CONVERSION_AUDIT_TRAIL_TO_CONVERT = '/POS to CDIS/For Conversion/Audit Trail/To Convert';
    const FOR_CONVERSION_AUDIT_TRAIL_PROCESSED = '/POS to CDIS/For Conversion/Audit Trail/Processed';
    const FOR_CONVERSION_AUDIT_TRAIL_FAILED = "/POS to CDIS/For Conversion/Audit Trail/Failed Conversion";
    const CONVERTED_AUDIT_TRAIL_TO_SYNC = "/POS to CDIS/Converted/Audit Trail/To Sync";
    const CONVERTED_AUDIT_TRAIL_SYNCED = "/POS to CDIS/Converted/Audit Trail/Synced";
    const CONVERTED_AUDIT_TRAIL_FAILED_SYNC = "/POS to CDIS/Converted/Audit Trail/Failed Sync";
    
    const FOR_CONVERSION_CASH_BREAKDOWN_TO_CONVERT = '/POS to CDIS/For Conversion/Cash Breakdown/To Convert';
    const FOR_CONVERSION_CASH_BREAKDOWN_PROCESSED = '/POS to CDIS/For Conversion/Cash Breakdown/Processed';
    const FOR_CONVERSION_CASH_BREAKDOWN_FAILED = "/POS to CDIS/For Conversion/Cash Breakdown/Failed Conversion";
    const CONVERTED_CASH_BREAKDOWN_TO_SYNC = "/POS to CDIS/Converted/Cash Breakdown/To Sync";
    const CONVERTED_CASH_BREAKDOWN_SYNCED = "/POS to CDIS/Converted/Cash Breakdown/Synced";
    const CONVERTED_CASH_BREAKDOWN_FAILED_SYNC = "/POS to CDIS/Converted/Cash Breakdown/Failed Sync";

    const FOR_CONVERSION_CASH_DRAWER_TO_CONVERT = '/POS to CDIS/For Conversion/Cash Drawer/To Convert';
    const FOR_CONVERSION_CASH_DRAWER_PROCESSED = '/POS to CDIS/For Conversion/Cash Drawer/Processed';
    const FOR_CONVERSION_CASH_DRAWER_FAILED = "/POS to CDIS/For Conversion/Cash Drawer/Failed Conversion";
    const CONVERTED_CASH_DRAWER_TO_SYNC = "/POS to CDIS/Converted/Cash Drawer/To Sync";
    const CONVERTED_CASH_DRAWER_SYNCED = "/POS to CDIS/Converted/Cash Drawer/Synced";
    const CONVERTED_CASH_DRAWER_FAILED_SYNC = "/POS to CDIS/Converted/Cash Drawer/Failed Sync";

    
    const FTP_TRANSACTION_TO_FETCH = "/Transaction/To fetch";
    const FTP_TRANSACTION_FETCHED = "/Transaction/Fetched";

    const FTP_ZREAD_TO_FETCH = "/Zread/To fetch";
    const FTP_ZREAD_FETCHED = "/Zread/Fetched";

    const FTP_AUDIT_TRAIL_TO_FETCH = "/Audit Trail/To fetch";
    const FTP_AUDIT_TRAIL_FETCHED = "/Audit Trail/Fetched";

    const FTP_CASH_BREAKDOWN_TO_FETCH = "/Cash Breakdown/To fetch";
    const FTP_CASH_BREAKDOWN_FETCHED = "/Cash Breakdown/Fetched";

    const FTP_CASH_DRAWER_TO_FETCH = "/Cash Drawer/To fetch";
    const FTP_CASH_DRAWER_FETCHED = "/Cash Drawer/Fetched";
}
