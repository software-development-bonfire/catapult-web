<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OpenSSL extends Enum
{
    const ENCRYPT_METHOD = 'AES-256-CBC';
    const SHA_256 = 'sha256';
    const SECRET_KEY = '1bce158cdb0f2cee45a5df9873210b18'; // bonfire-cdis
    const SECRET_IV = 'b0bad997c63ee726e5840cce2b99c476'; // 031819
}
