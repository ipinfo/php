<?php

namespace ipinfo\ipinfo\tests;

use ipinfo\ipinfo\IPinfo;
use ipinfo\ipinfo\IPinfoException;
use PHPUnit\Framework\TestCase;

class IPinfoTest extends TestCase
{
    public function testAccessToken()
    {
        $tok = '123';
        $client = new IPinfo($tok);
        $this->assertSame($tok, $client->access_token);
    }

    public function testDefaultCountries()
    {
        $client = new IPinfo();
        $this->assertSame('United States', $client->countries['US']);
        $this->assertSame('France', $client->countries['FR']);
    }

    public function testCustomCache()
    {
        $tok = 'this is a fake access token';
        $cache = 'this is a fake cache';
        $client = new IPinfo($tok, ['cache' => $cache]);
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
        $tok = 'this is a fake access token';
        $settings = ['cache_maxsize' => 100, 'cache_ttl' => 11];
        $client = new IPinfo($tok, $settings);
        $this->assertSame($settings['cache_maxsize'], $client->cache->maxsize);
        $this->assertSame($settings['cache_ttl'], $client->cache->ttl);
    }

    public function testFormatDetailsObject()
    {
        $test_details = [
            'country' => 'US',
            'loc' => '123,567'
        ];

        $h = new IPinfo();
        $res = $h->formatDetailsObject($test_details);

        $this->assertEquals($test_details['country'], $res->country);
        $this->assertEquals('United States', $res->country_name);
        $this->assertEquals($test_details['loc'], $res->loc);
        $this->assertEquals('123', $res->latitude);
        $this->assertEquals('567', $res->longitude);
    }

    public function testBadIP()
    {
        $ip = "fake_ip";
        $h = new IPinfo();
        $this->expectException(IPinfoException::class);
        $h->getDetails($ip);
    }

    public function testLookup()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        $h = new IPinfo($tok);
        $ip = "8.8.8.8";
        $res = $h->getDetails($ip);

        $this->assertEquals($res->ip, '8.8.8.8');
        $this->assertEquals($res->hostname, 'dns.google');
        $this->assertEquals($res->city, 'Mountain View');
        $this->assertEquals($res->region, 'California');
        $this->assertEquals($res->country, 'US');
        $this->assertEquals($res->country_name, 'United States');
        $this->assertEquals($res->loc, '37.4056,-122.0775');
        $this->assertEquals($res->latitude, '37.4056');
        $this->assertEquals($res->longitude, '-122.0775');
        $this->assertEquals($res->postal, '94043');
        $this->assertEquals($res->timezone, 'America/Los_Angeles');
        $this->assertEquals($res->asn['asn'], 'AS15169');
        $this->assertEquals($res->asn['name'], 'Google LLC');
        $this->assertEquals($res->asn['domain'], 'google.com');
        $this->assertEquals($res->asn['route'], '8.8.8.0/24');
        $this->assertEquals($res->asn['type'], 'business');
        $this->assertEquals($res->company['name'], 'Google LLC');
        $this->assertEquals($res->company['domain'], 'google.com');
        $this->assertEquals($res->company['type'], 'business');
        $this->assertEquals($res->privacy['vpn'], false);
        $this->assertEquals($res->privacy['proxy'], false);
        $this->assertEquals($res->privacy['tor'], false);
        $this->assertEquals($res->privacy['hosting'], false);
        $this->assertEquals($res->abuse['address'], 'US, CA, Mountain View, 1600 Amphitheatre Parkway, 94043');
        $this->assertEquals($res->abuse['country'], 'US');
        $this->assertEquals($res->abuse['email'], 'network-abuse@google.com');
        $this->assertEquals($res->abuse['name'], 'Abuse');
        $this->assertEquals($res->abuse['network'], '8.8.8.0/24');
        $this->assertEquals($res->abuse['phone'], '+1-650-253-0000');
        $this->assertEquals($res->domains['ip'], '8.8.8.8');
    }
}
