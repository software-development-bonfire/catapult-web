<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CommonErrors extends Enum
{
    const COULD_NOT_RESOLVE_HOST    = "Could not resolve host";
    const POST_METHOD_NOT_SUPPORTED = "The POST method is not supported for this route.";
    const OPEN_SSL_CONNECT          = "OpenSSL SSL_connect";
    const NO_FILE_REPORT_FOUND      = "No file report found.";
    const NOT_ALLOWED_FILE_EXT      = "not allowed file extension";
}
