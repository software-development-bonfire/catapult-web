<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Directory extends Enum
{
    const POS_TO_CDIS = "/POS to CDIS";

    const FOR_CONVERSION_TRANSACTION_TO_CONVERT = '/POS to CDIS/For Conversion/Transaction/To Convert';
    const FOR_CONVERSION_TRANSACTION_PROCESSED = '/POS to CDIS/For Conversion/Transaction/Processed';
    const FOR_CONVERSION_TRANSACTION_FAILED = "/POS to CDIS/For Conversion/Transaction/Failed Conversion";
    const CONVERTED_TRANSACTION_TO_SYNC = "/POS to CDIS/Converted/Transaction/To Sync";
    const CONVERTED_TRANSACTION_SYNCED = "/POS to CDIS/Converted/Transaction/Synced";
    const CONVERTED_TRANSACTION_FAILED_SYNC = "/POS to CDIS/Converted/Transaction/Failed Sync";
    
    const FOR_CONVERSION_ZREAD_TO_CONVERT = '/POS to CDIS/For Conversion/Zread/To Convert';
    const FAILED_CONVERSION_Zread = "/POS to CDIS/Zread/Failed Conversion";
    
    const FTP_TRANSACTION_TO_FETCH = "/Transaction/To fetch";
    const FTP_TRANSACTION_FETCHED = "/Transaction/Fetched";

    const FTP_ZREAD_TO_FETCH = "/Zread/To fetch";
    const FTP_ZREAD_FETCHED = "/Zread/Fetched";
}