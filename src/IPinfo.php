<?php

namespace jhtimmins\ipinfo;

require_once(__DIR__.'/cache/Default.php');

use GuzzleHttp\Exception\TransferException;

class IPinfo
{
    const API_URL = 'https://ipinfo.io';
    const CACHE_MAXSIZE = 4096;
    const CACHE_TTL = 60 * 60 * 24;
    const COUNTRIES_FILE_DEFAULT = __DIR__ . '/countries.json';
    const REQUEST_TYPE_GET = 'GET';
    const STATUS_CODE_QUOTA_EXCEEDED = 429;

    private $access_token;
    private $cache;
    private $countries;
    private $http_client;

    public function __construct($access_token = null, $settings = [])
    {
        $this->access_token = $access_token;
        $this->http_client = new \GuzzleHttp\Client(['http_errors' => false]);

        $countries_file = $settings['countries_file'] ?? self::COUNTRIES_FILE_DEFAULT;
        $this->countries = $this->readCountryNames($countries_file);

        if (array_key_exists('cache', $settings)) {
          $this->cache = $settings['cache'];
        } else {
          $maxsize = $settings['cache_maxsize'] ?? self::CACHE_MAXSIZE;
          $ttl = $settings['cache_ttl'] ?? self::CACHE_TTL;
          $this->cache = new cache\DefaultCache($maxsize, $ttl);
        }
    }

    public function getDetails($ip_address = null)
    {
        $response = $this->getRequestDetails((string) $ip_address);
        $raw_details = json_decode($response->getBody(), true);
        $raw_details['country_name'] = $this->countries[$raw_details['country']] ?? null;

        if (array_key_exists('loc', $raw_details)) {
          $coords = explode(',', $raw_details['loc']);
          $raw_details['latitude'] = $coords[0];
          $raw_details['longitude'] = $coords[1];
        } else {
          $raw_details['latitude'] = null;
          $raw_details['longitude'] = null;
        }

        return new Details($raw_details);
    }

    private function getRequestDetails(string $ip_address)
    {
      if (!$this->cache->has($ip_address)) {
        $url = self::API_URL;
        if ($ip_address) {
          $url .= "/$ip_address";
        }

        $response = $this->http_client->request(
          self::REQUEST_TYPE_GET,
          $url,
          $this->getHeaders()
        );

        if ($response->getStatusCode() == self::STATUS_CODE_QUOTA_EXCEEDED) {
          throw new Exception('IPinfo request quota exceeded.');
        } elseif ($response->getStatusCode() >= 400) {
          throw new Exception('Exception: ' . json_encode([
            'status' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
          ]));
        }
        $this->cache->set($ip_address, $response);
      }

      return $this->cache->get($ip_address);
    }

    private function getHeaders()
    {
      $headers = [
        'user-agent' => 'IPinfoClient/PHP/1.0',
        'accept' => 'application/json',
      ];

      if ($this->access_token) {
        $headers['authorization'] = "Bearer {$this->access_token}";
      }

      return $headers;
    }

    private function readCountryNames($countries_file)
    {
      $file_contents = file_get_contents($countries_file);
      return json_decode($file_contents, true);
    }
}
