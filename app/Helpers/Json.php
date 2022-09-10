<?php

if (!function_exists('stringToJson')) {

    function stringToJson($data)
    {
        return is_array($data) || is_object($data) ? (object) $data : json_decode($data);
    }
}

if (!function_exists('isValidJson')) {

    function isValidJson($data)
    {
        if (!empty($data)) {
            @json_decode($data);
            return (json_last_error() === JSON_ERROR_NONE);
        }
    }
}

if (!function_exists('isJsonExtension')) {

    function isJsonExtension($filename)
    {
        $explodedFilename = explode('.', $filename);
        $extension = end($explodedFilename);
        return strtolower($extension) === 'json';
    }
}
