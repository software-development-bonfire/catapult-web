<?php

namespace App\Services\CDIS\V2;

use App\Exceptions\Paymongo\ApiException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Class CDISApiService
 *
 * Handles low-level HTTP communication with the PayMongo API
 * using Guzzle HTTP client.
 */
class CDISApiService
{
    /**
     * @var Client Guzzle HTTP client instance
     */
    protected $client;

    /**
     * @var string PayMongo Secret API Key
     */
    protected $secretKey;

    /**
     * @var string Base URL for PayMongo API
     */
    protected $baseUrl;

    /**
     * PaymongoApiService constructor.
     * Initializes Guzzle client with PayMongo credentials.
     *
     * @throws InvalidArgumentException if secret key is missing
     */
    public function __construct()
    {
        $this->baseUrl = config('app.cdis_url');
        // Initialize Guzzle client with base URI and headers
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                // 'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'), // Basic Auth (Base64 encoded)
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Sends a POST request to the PayMongo API.
     *
     * @param string $uri      The endpoint URI (e.g., /payment_intents)
     * @param array $payload   The JSON payload to send
     *
     * @return array           The response data (typically in 'data' key)
     *
     * @throws \Exception      On request failure
     */
    public function post(string $uri, array $payload)
    {
        try {
            $response = $this->client->post($uri, ['json' => $payload]);

            $json = json_decode($response->getBody(), true);
            return $json['data'] ?? $json;
        } catch (RequestException $e) {
            // Try to extract error response from the API if available
            $error = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody(), true)
                : ['error' => $e->getMessage()];

            throw new \Exception('CDIS POST Error: ' . json_encode($error));
        }
    }

    /**
     * Sends a GET request to the PayMongo API.
     *
     * @param string $uri      The endpoint URI (e.g., /webhooks)
     *
     * @return array           The response data (typically in 'data' key)
     *
     * @throws \Exception      On request failure
     */
    public function get(string $uri)
    {
        //try {
            $response = $this->client->get($uri);

            Log::alert(json_encode(['response' => $response]));
            $json = json_decode($response->getBody(), true);
            return $json['data'] ?? $json;
            /*
        } catch (RequestException $e) {
            $response = '';
            $error = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody(), true)
                : ['error' => $e->getMessage()];

            throw new \Exception('Paymongo GET Error: ' . json_encode($error));
        }*/
    }
}
