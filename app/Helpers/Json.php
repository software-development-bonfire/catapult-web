<?php

if (!function_exists('stringToJson')) {

    function stringToJson($data)
    {
        return is_array($data) || is_object($data) ? (object) $data : json_decode($data);
    }
}
