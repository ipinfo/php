<?php

namespace ipinfo\ipinfo;

use Exception;
use ipinfo\ipinfo\cache\DefaultCache;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Promise;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Exposes the IPinfo library to client code.
 */
class IPinfo
{
    const API_URL = 'https://ipinfo.io';
    const COUNTRY_FLAG_URL = 'https://cdn.ipinfo.io/static/images/countries-flags/';
    const STATUS_CODE_QUOTA_EXCEEDED = 429;
    const REQUEST_TIMEOUT_DEFAULT = 2; // seconds

    const CACHE_MAXSIZE = 4096;
    const CACHE_TTL = 86400; // 24 hours as seconds
    const CACHE_KEY_VSN = '1'; // update when cache vals change for same key.

    const COUNTRIES_FILE_DEFAULT = __DIR__ . '/countries.json';
    const COUNTRIES_FLAGS_FILE_DEFAULT = __DIR__ . '/flags.json';
    const EU_COUNTRIES_FILE_DEFAULT = __DIR__ . '/eu.json';
    const COUNTRIES_CURRENCIES_FILE_DEFAULT = __DIR__ . '/currency.json';
    const CONTINENT_FILE_DEFAULT = __DIR__ . '/continent.json';

    const BATCH_MAX_SIZE = 1000;
    const BATCH_TIMEOUT = 5; // seconds

    public $access_token;
    public $settings;
    public $cache;
    public $countries;
    public $eu_countries;
    public $countries_flags;
    public $countries_currencies;
    public $continents;
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
        $countries_flags_file = $settings['countries_flags_file'] ?? self::COUNTRIES_FLAGS_FILE_DEFAULT;
        $countries_currencies_file = $settings['countries_currencies_file'] ?? self::COUNTRIES_CURRENCIES_FILE_DEFAULT;
        $eu_countries_file = $settings['eu_countries_file'] ?? self::EU_COUNTRIES_FILE_DEFAULT;
        $continents_file = $settings['continent_file'] ?? self::CONTINENT_FILE_DEFAULT;
        $this->countries = $this->readJSONFile($countries_file);
        $this->countries_flags = $this->readJSONFile($countries_flags_file);
        $this->countries_currencies = $this->readJSONFile($countries_currencies_file);
        $this->eu_countries = $this->readJSONFile($eu_countries_file);
        $this-> continents = $this->readJSONFile($continents_file);

        if (!array_key_exists('cache_disabled', $this->settings) || $this->settings['cache_disabled'] == false) {
            if (array_key_exists('cache', $settings)) {
                $this->cache = $settings['cache'];
            } else {
                $maxsize = $settings['cache_maxsize'] ?? self::CACHE_MAXSIZE;
                $ttl = $settings['cache_ttl'] ?? self::CACHE_TTL;
                $this->cache = new DefaultCache($maxsize, $ttl);
            }
        } else {
            $this->cache = null;
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
        if ($this->cache != null) {
            foreach ($urls as $url) {
                $cachedRes = $this->cache->get($this->cacheKey($url));
                if ($cachedRes != null) {
                    $results[$url] = $cachedRes;
                } else {
                    $lookupUrls[] = $url;
                }
            }
        } else {
            $lookupUrls = $urls;
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
        $details['country_flag'] = $this->countries_flags[$country] ?? null;
        $details['country_flag_url'] = self::COUNTRY_FLAG_URL.$country.".svg";
        $details['country_currency'] = $this->countries_currencies[$country] ?? null;
        $details['continent'] = $this->continents[$country] ?? null;

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
        if ($this->isBogon($ip_address)) {
            return [
                "ip" => $ip_address,
                "bogon" => true,
            ];
        }

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
            'user-agent' => 'IPinfoClient/PHP/3.1.2',
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
        return sprintf('%s_v%s', $k, self::CACHE_KEY_VSN);
    }

    /**
     * Check if an IP address is a bogon.
     *
     * @param string $ip The IP address to check
     * @return bool True if the IP address is a bogon, false otherwise
     */
    public function isBogon($ip)
    {
        // Check if the IP address is in the range
        return IpUtils::checkIp($ip, $this->bogonNetworks);
    }

    // List of bogon CIDRs.
    protected $bogonNetworks = [
        "0.0.0.0/8",
        "10.0.0.0/8",
        "100.64.0.0/10",
        "127.0.0.0/8",
        "169.254.0.0/16",
        "172.16.0.0/12",
        "192.0.0.0/24",
        "192.0.2.0/24",
        "192.168.0.0/16",
        "198.18.0.0/15",
        "198.51.100.0/24",
        "203.0.113.0/24",
        "224.0.0.0/4",
        "240.0.0.0/4",
        "255.255.255.255/32",
        "::/128",
        "::1/128",
        "::ffff:0:0/96",
        "::/96",
        "100::/64",
        "2001:10::/28",
        "2001:db8::/32",
        "fc00::/7",
        "fe80::/10",
        "fec0::/10",
        "ff00::/8",
        "2002::/24",
        "2002:a00::/24",
        "2002:7f00::/24",
        "2002:a9fe::/32",
        "2002:ac10::/28",
        "2002:c000::/40",
        "2002:c000:200::/40",
        "2002:c0a8::/32",
        "2002:c612::/31",
        "2002:c633:6400::/40",
        "2002:cb00:7100::/40",
        "2002:e000::/20",
        "2002:f000::/20",
        "2002:ffff:ffff::/48",
        "2001::/40",
        "2001:0:a00::/40",
        "2001:0:7f00::/40",
        "2001:0:a9fe::/48",
        "2001:0:ac10::/44",
        "2001:0:c000::/56",
        "2001:0:c000:200::/56",
        "2001:0:c0a8::/48",
        "2001:0:c612::/47",
        "2001:0:c633:6400::/56",
        "2001:0:cb00:7100::/56",
        "2001:0:e000::/36",
        "2001:0:f000::/36",
        "2001:0:ffff:ffff::/64"
    ];
}
