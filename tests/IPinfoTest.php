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

        // test multiple times for cache hits
        for ($i = 0; $i < 5; $i++) {
            $res = $h->getDetails($ip);
            $this->assertEquals($res->ip, '8.8.8.8');
            $this->assertEquals($res->hostname, 'dns.google');
            $this->assertEquals($res->city, 'Mountain View');
            $this->assertEquals($res->region, 'California');
            $this->assertEquals($res->country, 'US');
            $this->assertEquals($res->country_name, 'United States');
            $this->assertEquals($res->is_eu, false);
            $this->assertEquals($res->loc, '37.4056,-122.0775');
            $this->assertEquals($res->latitude, '37.4056');
            $this->assertEquals($res->longitude, '-122.0775');
            $this->assertEquals($res->postal, '94043');
            $this->assertEquals($res->timezone, 'America/Los_Angeles');
            $this->assertEquals($res->asn['asn'], 'AS15169');
            $this->assertEquals($res->asn['name'], 'Google LLC');
            $this->assertEquals($res->asn['domain'], 'google.com');
            $this->assertEquals($res->asn['route'], '8.8.8.0/24');
            $this->assertEquals($res->asn['type'], 'hosting');
            $this->assertEquals($res->company['name'], 'Google LLC');
            $this->assertEquals($res->company['domain'], 'google.com');
            $this->assertEquals($res->company['type'], 'hosting');
            $this->assertEquals($res->privacy['vpn'], false);
            $this->assertEquals($res->privacy['proxy'], false);
            $this->assertEquals($res->privacy['tor'], false);
            $this->assertEquals($res->privacy['relay'], false);
            $this->assertEquals($res->privacy['hosting'], true);
            $this->assertEquals($res->privacy['service'], '');
            $this->assertEquals($res->abuse['address'], 'US, CA, Mountain View, 1600 Amphitheatre Parkway, 94043');
            $this->assertEquals($res->abuse['country'], 'US');
            $this->assertEquals($res->abuse['email'], 'network-abuse@google.com');
            $this->assertEquals($res->abuse['name'], 'Abuse');
            $this->assertEquals($res->abuse['network'], '8.8.8.0/24');
            $this->assertEquals($res->abuse['phone'], '+1-650-253-0000');
            $this->assertEquals($res->domains['ip'], '8.8.8.8');
        }
    }

    public function testGuzzleOverride()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        $h = new IPinfo($tok, ['guzzle_opts' => [
            'headers' => [
                'authorization' => 'Bearer blah'
            ],
        ]]);
        $ip = "8.8.8.8";

        $this->expectException(IPinfoException::class);
        $res = $h->getDetails($ip);
    }

    public function testGetMapURL()
    {
        $h = new IPinfo();
        $url = $h->getMapUrl(file("tests/map-ips.txt"));
        $this->assertStringStartsWith("https://ipinfo.io/tools/map/", $url);
    }

    public function testGetBatchDetails()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        $h = new IPinfo($tok);

        // test multiple times for cache.
        for ($i = 0; $i < 10; $i++) {
            $res = $h->getBatchDetails([
                '8.8.8.8/hostname',
                'AS123',
                '1.1.1.1',
                '2.2.2.2',
                '3.3.3.3',
                '4.4.4.4',
                '5.5.5.5',
                '6.6.6.6',
                '7.7.7.7',
                '8.8.8.8',
                '9.9.9.9',
                '10.10.10.10'
            ], 3, IPinfo::BATCH_TIMEOUT, true);

            $this->assertArrayHasKey('8.8.8.8/hostname', $res);
            $this->assertArrayHasKey('AS123', $res);
            $this->assertArrayHasKey('1.1.1.1', $res);
            $this->assertArrayHasKey('2.2.2.2', $res);
            $this->assertArrayHasKey('3.3.3.3', $res);
            $this->assertArrayHasKey('4.4.4.4', $res);
            $this->assertArrayHasKey('5.5.5.5', $res);
            $this->assertArrayHasKey('6.6.6.6', $res);
            $this->assertArrayHasKey('7.7.7.7', $res);
            $this->assertArrayHasKey('8.8.8.8', $res);
            $this->assertArrayHasKey('9.9.9.9', $res);
            $this->assertArrayHasKey('10.10.10.10', $res);

            $this->assertEquals($res['8.8.8.8/hostname'], 'dns.google');
            $this->assertEquals($res['4.4.4.4'], [
                'ip' => "4.4.4.4",
                'city' => "Broomfield",
                'region' => "Colorado",
                'country' => "US",
                'loc' => "39.8854,-105.1139",
                'org' => "AS3356 Level 3 Parent, LLC",
                'postal' => "80021",
                'timezone' => "America/Denver",
                'asn' => [
                    'asn' => "AS3356",
                    'name' => "Level 3 Parent, LLC",
                    'domain' => "lumen.com",
                    'route' => "4.0.0.0/9",
                    'type' => "isp"
                ],
                'company' => [
                    'name' => "Level 3 Communications, Inc.",
                    'domain' => "lumen.com",
                    'type' => "isp"
                ],
                'privacy' => [
                    'vpn' => false,
                    'proxy' => false,
                    'tor' => false,
                    'relay' => false,
                    'hosting' => false,
                    'service' => ""
                ],
                'abuse' => [
                    'address' => "US, CO, Broomfield, 1025 Eldorado Blvd., 80021",
                    'country' => "US",
                    'email' => "abuse@level3.com",
                    'name' => "Abuse POC LVLT",
                    'network' => "4.4.0.0/16",
                    'phone' => "+1-877-453-8353"
                ],
                'domains' => [
                    'ip' => "4.4.4.4",
                    'total' => 125,
                    'domains' => [
                        'ddosxtesting.co.uk',
                        'planningone.co',
                        'pristineplanet.org',
                        'datacenterteam.de',
                        'micotan.ca'
                    ]
                ]
            ]);

            $this->assertEquals($res['AS123'], [
                'asn' => "AS123",
                'name' => "Air Force Systems Networking",
                'country' => "US",
                'allocated' => "1987-08-24",
                'registry' => "arin",
                'domain' => "af.mil",
                'num_ips' => 0,
                'type' => "inactive",
                'prefixes' => [],
                'prefixes6' => [],
                'peers' => null,
                'upstreams' => null,
                'downstreams' => null
            ]);
        }
    }
}
