<?php

namespace ipinfo\ipinfo\tests;

use ipinfo\ipinfo\IPinfo;
use ipinfo\ipinfo\IPinfoException;
use PHPUnit\Framework\TestCase;

class IPinfoTest extends TestCase
{
    public function testAccessToken()
    {
      $access_token = '123';
      $client = new IPinfo($access_token);
      $this->assertSame($access_token, $client->access_token);
    }

    public function testDefaultCountries()
    {
      $client = new IPinfo();
      $this->assertSame('United States', $client->countries['US']);
      $this->assertSame('France', $client->countries['FR']);
    }

    public function testCustomCache()
    {
      $access_token = 'this is a fake access token';
      $cache = 'this is a fake cache';
      $client = new IPinfo($access_token, ['cache' => $cache]);
      $this->assertSame($cache, $client->cache);
    }

    public function testDefaultCacheSettings()
    {
      $client = new IPinfo();
      $this->assertSame(IPinfo::CACHE_MAXSIZE, $client->cache->maxsize);
      $this->assertSame(IPinfo::CACHE_TTL, $client->cache->ttl);
    }

    public function testCustomCacheSettings()
    {
      $access_token = 'this is a fake access token';
      $settings = ['cache_maxsize' => 100, 'cache_ttl' => 11];
      $client = new IPinfo($access_token, $settings);
      $this->assertSame($settings['cache_maxsize'], $client->cache->maxsize);
      $this->assertSame($settings['cache_ttl'], $client->cache->ttl);
    }

    public function testFormatDetailsObject()
    {
      $test_details = [
        'country' => 'US',
        'loc' => '123,567'
      ];

      $handler = new IPinfo();
      $details = $handler->formatDetailsObject($test_details);

      $this->assertEquals($test_details['country'], $details->country);
      $this->assertEquals('United States', $details->country_name);
      $this->assertEquals($test_details['loc'], $details->loc);
      $this->assertEquals('123', $details->latitude);
      $this->assertEquals('567', $details->longitude);
    }

    public function testBuildHeaders()
    {
      $token = '123abc';

      $handler = new IPinfo($token);
      $headers = $handler->buildHeaders();

      $this->assertArrayHasKey('headers', $headers);
      $headers = $headers['headers'];
      $this->assertEquals("IPinfoClient/PHP/1.0", $headers['user-agent']);
      $this->assertEquals("application/json", $headers['accept']);
      $this->assertEquals("Bearer $token", $headers['authorization']);
    }

    public function testBadIP()
    {
      $ip = "fake_ip";
      $handler = new IPinfo();
      $this->expectException(IPinfoException::class);
      $handler->getDetails($ip);
    }
}
