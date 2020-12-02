<?php

namespace ipinfo\ipinfo;

use Exception;
use ipinfo\ipinfo\cache\DefaultCache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Exposes the IPinfo library to client code.
 */
class IPinfo
{
    const API_URL = 'https://ipinfo.io';
    const CACHE_MAXSIZE = 4096;
    const CACHE_TTL = 86400; // 24 hours as seconds
    const COUNTRIES_FILE_DEFAULT = __DIR__ . '/countries.json';
    const REQUEST_TYPE_GET = 'GET';
    const STATUS_CODE_QUOTA_EXCEEDED = 429;
    const REQUEST_TIMEOUT_DEFAULT = 2; // seconds

    public $access_token;
    public $cache;
    public $countries;
    protected $http_client;

    public function __construct($access_token = null, $settings = [])
    {
        $this->access_token = $access_token;

        /*
        Support a timeout first-class, then a `guzzle_opts` key that can
        override anything.
        */
        $guzzle_opts = [
            'http_errors' => false,
            'headers' => $this->buildHeaders(),
            'timeout' => $settings['timeout'] ?? self::REQUEST_TIMEOUT_DEFAULT
        ];
        if (isset($settings['guzzle_opts'])) {
            $guzzle_opts = array_merge($guzzle_opts, $settings['guzzle_opts']);
        }
        $this->http_client = new Client($guzzle_opts);

        $countries_file = $settings['countries_file'] ?? self::COUNTRIES_FILE_DEFAULT;
        $this->countries = $this->readCountryNames($countries_file);

        if (array_key_exists('cache', $settings)) {
            $this->cache = $settings['cache'];
        } else {
            $maxsize = $settings['cache_maxsize'] ?? self::CACHE_MAXSIZE;
            $ttl = $settings['cache_ttl'] ?? self::CACHE_TTL;
            $this->cache = new DefaultCache($maxsize, $ttl);
        }
    }

    /**
     * Get formatted details for an IP address.
     * @param  string|null $ip_address IP address to look up.
     * @return Details Formatted IPinfo data.
     * @throws IPinfoException
     */
    public function getDetails($ip_address = null)
    {
        $response_details = $this->getRequestDetails((string) $ip_address);
        return $this->formatDetailsObject($response_details);
    }

    /**
     * Format details and return as an object.
     * @param  array  $details IP address details.
     * @return Details Formatted IPinfo Details object.
     */
    public function formatDetailsObject($details = [])
    {
        $country = $details['country'] ?? null;
        $details['country_name'] = $this->countries[$country] ?? null;

        if (array_key_exists('loc', $details)) {
            $coords = explode(',', $details['loc']);
            $details['latitude'] = $coords[0];
            $details['longitude'] = $coords[1];
        } else {
            $details['latitude'] = null;
            $details['longitude'] = null;
        }

        return new Details($details);
    }

    /**
     * Get details for a specific IP address.
     * @param  string $ip_address IP address to query API for.
     * @return array IP response data.
     * @throws IPinfoException
     */
    public function getRequestDetails(string $ip_address)
    {
        if ($this->cache->has($ip_address)) {
            return $this->cache->get($ip_address);
        }

        $url = self::API_URL;
        if ($ip_address) {
            $url .= "/$ip_address";
        }

        try {
            $response = $this->http_client->request(
                self::REQUEST_TYPE_GET,
                $url
            );
        } catch (GuzzleException $e) {
            throw new IPinfoException($e->getMessage());
        } catch (Exception $e) {
            throw new IPinfoException($e->getMessage());
        }

        if ($response->getStatusCode() == self::STATUS_CODE_QUOTA_EXCEEDED) {
            throw new IPinfoException('IPinfo request quota exceeded.');
        } elseif ($response->getStatusCode() >= 400) {
            throw new IPinfoException('Exception: ' . json_encode([
                'status' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
            ]));
        }

        $raw_details = json_decode($response->getBody(), true);
        $this->cache->set($ip_address, $raw_details);

        return $raw_details;
    }

    /**
     * Build headers for API request.
     * @return array Headers for API request.
     */
    private function buildHeaders()
    {
        $headers = [
            'user-agent' => 'IPinfoClient/PHP/2.1',
            'accept' => 'application/json',
        ];

        if ($this->access_token) {
            $headers['authorization'] = "Bearer {$this->access_token}";
        }

        return $headers;
    }

    /**
     * Read country names from a file and return as an array.
     * @param  string $countries_file JSON file of country_code => country_name mappings
     * @return array country_code => country_name mappings
     */
    private function readCountryNames($countries_file)
    {
        $file_contents = file_get_contents($countries_file);
        return json_decode($file_contents, true);
    }
}
