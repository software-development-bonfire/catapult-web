<?php

namespace App\Helpers;

use Exception;

class SimpleEncryption
{
    private static $key = null; // 32 chars
    private static $iv = null; // 16 chars
    private static $cipher = 'aes-256-cbc';

    /**
     * Initialize the encryption with secret key and IV
     * Call once at app start (or before first use)
     */
    public static function init($secretKey, $secretIv)
    {
        // PHP: hash('sha256', secret) returns 64 hex chars
        $keyHex = hash('sha256', $secretKey); // 64 chars
        $ivHex = hash('sha256', $secretIv);   // 64 chars

        // Use first 32 ASCII chars as key, 16 for IV (matching Dart implementation)
        $keyAscii32 = substr($keyHex, 0, 32); // 32 bytes (ASCII)
        $ivAscii16 = substr($ivHex, 0, 16);   // 16 bytes (ASCII)

        self::$key = $keyAscii32;
        self::$iv = $ivAscii16;

        // Test decryption (equivalent to your Logger.warn line)
        // $test = self::decryptText('6is+hqoOP73OmQEcBSImuw==');
        // error_log("Test decryption: " . $test);
    }

    /**
     * Encrypt plain text
     */
    public static function encryptText($plainText)
    {
        self::ensureInit();

        $encrypted = openssl_encrypt(
            $plainText,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            self::$iv
        );

        if ($encrypted === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt base64 encoded text
     */
    public static function decryptText($base64Text)
    {
        self::ensureInit();

        $encrypted = base64_decode($base64Text);
        if ($encrypted === false) {
            throw new \Exception('Invalid base64 string');
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            self::$iv
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Encrypt JSON object
     */
    public static function encryptJson($data)
    {
        $jsonString = json_encode($data);
        if ($jsonString === false) {
            throw new Exception('JSON encoding failed');
        }
        return self::encryptText($jsonString);
    }

    /**
     * Decrypt JSON object
     */
    public static function decryptJson($base64Text)
    {
        $decrypted = self::decryptText($base64Text);
        $data = json_decode($decrypted, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decoding failed: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Encrypt object (generic version)
     */
    public static function encryptObject($object)
    {
        if (method_exists($object, 'toArray')) {
            $data = $object->toArray();
        } elseif (method_exists($object, 'toJson')) {
            $data = json_decode($object->toJson(), true);
        } else {
            $data = (array)$object;
        }

        return self::encryptJson($data);
    }

    /**
     * Decrypt object (generic version)
     */
    public static function decryptObject($base64Text, $className = null)
    {
        try {
            $data = self::decryptJson($base64Text);

            if ($className && class_exists($className)) {
                if (method_exists($className, 'fromArray')) {
                    return call_user_func([$className, 'fromArray'], $data);
                } else {
                    $object = new $className();
                    foreach ($data as $key => $value) {
                        if (property_exists($object, $key)) {
                            $object->$key = $value;
                        }
                    }
                    return $object;
                }
            }

            return $data;
        } catch (Exception $e) {
            error_log("Decryption failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ensure encryption is initialized
     */
    private static function ensureInit()
    {
        self::init(\App\Enums\OpenSSL::SECRET_KEY, \App\Enums\OpenSSL::SECRET_IV);
        if (self::$key === null || self::$iv === null) {
            throw new Exception('SimpleEncryption::init() was not called.');
        }
    }
}

// Interface for serializable objects (optional)
interface SerializableConfig
{
    public function toArray();
    public function isConfigured();
}

// Usage example:
/*
// Initialize once at application start
SimpleEncryption::init('your-secret-key', 'your-secret-iv');

// Example 1: Basic text encryption/decryption
$originalText = "Hello, cross-platform encryption!";
$encrypted = SimpleEncryption::encryptText($originalText);
echo "Encrypted: " . $encrypted . "\n";

$decrypted = SimpleEncryption::decryptText($encrypted);
echo "Decrypted: " . $decrypted . "\n";

// Example 2: JSON encryption/decryption
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
];

$encryptedJson = SimpleEncryption::encryptJson($data);
echo "Encrypted JSON: " . $encryptedJson . "\n";

$decryptedData = SimpleEncryption::decryptJson($encryptedJson);
print_r($decryptedData);

// Example 3: Test with your Dart encrypted string
try {
    $dartEncrypted = '6is+hqoOP73OmQEcBSImuw==';
    $decryptedFromDart = SimpleEncryption::decryptText($dartEncrypted);
    echo "Decrypted from Dart: " . $decryptedFromDart . "\n";
} catch (Exception $e) {
    echo "Error decrypting Dart data: " . $e->getMessage() . "\n";
}
*/
