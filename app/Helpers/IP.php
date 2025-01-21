<?php

namespace App\Helpers;

class IP
{
    # by: Cody Kochmann
    # returns true if the input is a valid ip address
    public static function validate($s)
    {
        # returns true if the $s is valid ip address
        return (int)(!filter_var($s, FILTER_VALIDATE_IP) === false);
    }

    # extracts potential ips from a string into an array
    public static function extract_legal_chars($s)
    {
        preg_match_all(
            '/([a-fA-F0-9\.\:])+/',
            $s,
            $strings_with_legal_chars
        );
        return $strings_with_legal_chars[0];
    }

    # returns an array of valid IP addresses extracted from
    # a string or array of strings
    public static function extract($s)
    {
        try {
            $targets = IP::extract_legal_chars(is_array($s) ? implode(' ', $s) : $s);
            $output = array();
            foreach ($targets as $key => $value) {
                if (IP::validate($value)) {
                    array_push($output, $value);
                }
            }
            $output = array_unique($output);
            return $output;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
