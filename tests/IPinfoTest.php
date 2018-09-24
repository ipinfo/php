<?php

namespace jhtimmins\ipinfo;
use jhtimmins\ipinfo\IPinfo;
use jhtimmins\ipinfo\Details;

class IPinfoTest extends \PHPUnit\Framework\TestCase
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
}
