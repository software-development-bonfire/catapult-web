<?php

if (! function_exists('encrypter')) {

    function encrypter($value)
    {
        $key = hash(\App\Enums\OpenSSL::SHA_256, \App\Enums\OpenSSL::SECRET_KEY);
        $iv = substr(hash(\App\Enums\OpenSSL::SHA_256, \App\Enums\OpenSSL::SECRET_IV), 0, 16);
        $output = openssl_encrypt($value, \App\Enums\OpenSSL::ENCRYPT_METHOD, $key, 0, $iv);
        $output = base64_encode($output);

        return $output;
    }
}

if (! function_exists('decrypter')) {

    function decrypter($encryptedValue)
    {
        $key = hash(\App\Enums\OpenSSL::SHA_256, \App\Enums\OpenSSL::SECRET_KEY);
        $iv = substr(hash(\App\Enums\OpenSSL::SHA_256, \App\Enums\OpenSSL::SECRET_IV), 0, 16);
        $output = openssl_decrypt(base64_decode($encryptedValue), \App\Enums\OpenSSL::ENCRYPT_METHOD, $key, 0, $iv);

        return $output;
    }
}



