<?php

namespace ipinfo\ipinfo;

use Exception;
use ipinfo\ipinfo\cache\DefaultCache;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Promise;

/**
 * Exposes the IPinfo library to client code.
 */
class IPinfo
{
    const API_URL = 'https://ipinfo.io';
    const STATUS_CODE_QUOTA_EXCEEDED = 429;
    const REQUEST_TIMEOUT_DEFAULT = 2; // seconds

    const CACHE_MAXSIZE = 4096;
    const CACHE_TTL = 86400; // 24 hours as seconds
    const CACHE_KEY_VSN = '1'; // update when cache vals change for same key.

    const COUNTRIES_FILE_DEFAULT = __DIR__ . '/countries.json';
    const EU_COUNTRIES_FILE_DEFAULT = __DIR__ . '/eu.json';

    const BATCH_MAX_SIZE = 1000;
    const BATCH_TIMEOUT = 5; // seconds

    public $access_token;
    public $cache;
    public $countries;
    public $eu_countries;
    protected $http_client;

    public function __construct($access_token = null, $settings = [])
    {
        $this->access_token = $access_token;
        $this->settings = $settings;

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
        $eu_countries_file = $settings['eu_countries_file'] ?? self::EU_COUNTRIES_FILE_DEFAULT;
        $this->countries = $this->readJSONFile($countries_file);
        $this->eu_countries = $this->readJSONFile($eu_countries_file);

        if (array_key_exists('cache_disabled', $this->settings) && $this->settings['cache_disabled'] != false) {
            $this->cache = null;
        } else {
            if (array_key_exists('cache', $settings)) {
                $this->cache = $settings['cache'];
            } else {
                $maxsize = $settings['cache_maxsize'] ?? self::CACHE_MAXSIZE;
                $ttl = $settings['cache_ttl'] ?? self::CACHE_TTL;
                $this->cache = new DefaultCache($maxsize, $ttl);
            }
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
     * Get formatted details for a list of IP addresses.
     * @param $urls the array of URLs.
     * @param $batchSize default value is set to max value for batch size, which is 1000.
     * @param batchTimeout in seconds. Default value is 5 seconds.
     * @param filter default value is false.
     * @return $results
     */
    public function getBatchDetails(
        $urls,
        $batchSize = 0,
        $batchTimeout = self::BATCH_TIMEOUT,
        $filter = false
    ) {
        $lookupUrls = [];
        $results = [];

        // no items?
        if (count($urls) == 0) {
            return $results;
        }

        // clip batch size.
        if (!is_numeric($batchSize) || $batchSize <= 0 || $batchSize > self::BATCH_MAX_SIZE) {
            $batchSize = self::BATCH_MAX_SIZE;
        }

        // filter out URLs already cached.
        if ($this->cache == null) {
            $lookupUrls = $urls;
        } else {
            foreach ($urls as $url) {
                $cachedRes = $this->cache->get($this->cacheKey($url));
                if ($cachedRes != null) {
                    $results[$url] = $cachedRes;
                } else {
                    $lookupUrls[] = $url;
                }
            }
        }

        // everything cached? exit early.
        if (count($lookupUrls) == 0) {
            return $results;
        }

        // prepare each batch & fire it off asynchronously.
        $apiUrl = self::API_URL . "/batch";
        if ($filter) {
            $apiUrl .= '?filter=1';
        }
        $promises = [];
        $totalBatches = ceil(count($lookupUrls) / $batchSize);
        for ($i = 0; $i < $totalBatches; $i++) {
            $start = $i * $batchSize;
            $batch = array_slice($lookupUrls, $start, $batchSize);
            $promise = $this->http_client->postAsync($apiUrl, [
                'body' => json_encode($batch),
                'timeout' => $batchTimeout
            ])->then(function ($resp) use (&$results) {
                $batchResult = json_decode($resp->getBody(), true);
                foreach ($batchResult as $k => $v) {
                    $results[$k] = $v;
                }
            });
            $promises[] = $promise;
        }

        // wait for all batches to finish.
        Promise\Utils::settle($promises)->wait();

        // cache any new results.
        if ($this->cache != null) {
            foreach ($lookupUrls as $url) {
                if (array_key_exists($url, $results)) {
                    $this->cache->set($this->cacheKey($url), $results[$url]);
                }
            }
        }

        return $results;
    }

    public function formatDetailsObject($details = [])
    {
        $country = $details['country'] ?? null;
        $details['country_name'] = $this->countries[$country] ?? null;
        $details['is_eu'] = in_array($country, $this->eu_countries);

        if (!array_key_exists('loc', $details)) {
            $details['latitude'] = null;
            $details['longitude'] = null;
        } else {
            $coords = explode(',', $details['loc']);
            $details['latitude'] = $coords[0];
            $details['longitude'] = $coords[1];
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
        if ($this->cache != null) {
            $cachedRes = $this->cache->get($this->cacheKey($ip_address));
            if ($cachedRes != null) {
                return $cachedRes;
            }
        }

        $url = self::API_URL;
        if ($ip_address) {
            $url .= "/$ip_address";
        }

        try {
            $response = $this->http_client->request('GET', $url);
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

        if ($this->cache != null) {
            $this->cache->set($this->cacheKey($ip_address), $raw_details);
        }

        return $raw_details;
    }

    /**
     * Gets a URL to a map on https://ipinfo.io/map given a list of IPs (max
     * 500,000).
     * @param array $ips list of IP addresses to put on the map.
     * @return string URL to the map.
     */
    public function getMapUrl($ips)
    {
        $url = sprintf("%s/map?cli=1", self::API_URL);

        try {
            $response = $this->http_client->request(
                'POST',
                $url,
                [
                    'json' => $ips
                ]
            );
        } catch (GuzzleException $e) {
            throw new IPinfoException($e->getMessage());
        } catch (Exception $e) {
            throw new IPinfoException($e->getMessage());
        }

        $res = json_decode($response->getBody(), true);
        return $res['reportUrl'];
    }

    /**
     * Build headers for API request.
     * @return array Headers for API request.
     */
    private function buildHeaders()
    {
        $headers = [
            'user-agent' => 'IPinfoClient/PHP/2.3.1',
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];

        if ($this->access_token) {
            $headers['authorization'] = "Bearer {$this->access_token}";
        }

        return $headers;
    }

    /**
     * Read JSON from a file and return as an array.
     * @param  string $countries_file JSON file of country_code => country_name mappings
     * @return array country_code => country_name mappings
     */
    private function readJSONFile($countries_file)
    {
        $file_contents = file_get_contents($countries_file);
        return json_decode($file_contents, true);
    }

    /**
     * Returns a versioned cache key given a user-input key.
     * @param  string $k key to transform into a versioned cache key.
     * @return string the versioned cache key.
     */
    private function cacheKey($k)
    {
        return sprintf('%s:%s', $k, self::CACHE_KEY_VSN);
    }
}
