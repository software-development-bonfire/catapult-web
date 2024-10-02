<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TokenResponseCode extends Enum
{
    const TOKEN_VALID = '200-01';
    const TOKEN_GENERATED = '200-02';

    const TOKEN_INVALID = '406-01';
    const TOKEN_EXPIRED = '406-02';
    const TOKEN_MALFORMED = '406-03';
    const TOKEN_REQUIRED = '406-04';    
    const TOKEN_ERROR = '406-05';
}
