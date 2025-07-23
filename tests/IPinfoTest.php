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
            $this->assertEquals($res->country_flag['emoji'], '🇺🇸');
            $this->assertEquals($res->country_flag_url, 'https://cdn.ipinfo.io/static/images/countries-flags/US.svg');
            $this->assertEquals($res->country_flag['unicode'], 'U+1F1FA U+1F1F8');
            $this->assertEquals($res->country_currency['code'], 'USD');
            $this->assertEquals($res->country_currency['symbol'], '$');
            $this->assertEquals($res->continent['code'], 'NA');
            $this->assertEquals($res->continent['name'], 'North America');
            $this->assertEquals($res->loc, '38.0088,-122.1175');
            $this->assertEquals($res->latitude, '38.0088');
            $this->assertEquals($res->longitude, '-122.1175');
            $this->assertEquals($res->postal, '94043');
            $this->assertEquals($res->timezone, 'America/Los_Angeles');
            if ($res->asn !== null) {
                $this->assertEquals($res->asn['asn'], 'AS15169');
                $this->assertEquals($res->asn['name'], 'Google LLC');
                $this->assertEquals($res->asn['domain'], 'google.com');
                $this->assertEquals($res->asn['route'], '8.8.8.0/24');
                $this->assertEquals($res->asn['type'], 'hosting');
            }
            if ($res->company !== null) {
                $this->assertEquals($res->company['name'], 'Google LLC');
                $this->assertEquals($res->company['domain'], 'google.com');
                $this->assertEquals($res->company['type'], 'hosting');
            }
            if ($res->privacy !== null) {
                $this->assertEquals($res->privacy['vpn'], false);
                $this->assertEquals($res->privacy['proxy'], false);
                $this->assertEquals($res->privacy['tor'], false);
                $this->assertEquals($res->privacy['relay'], false);
                if ($res->privacy['hosting'] !== null) {
                    $this->assertEquals($res->privacy['hosting'], true);
                }
                $this->assertEquals($res->privacy['service'], '');
            }
            if ($res->abuse !== null) {
                $this->assertEquals($res->abuse['address'], 'US, CA, Mountain View, 1600 Amphitheatre Parkway, 94043');
                $this->assertEquals($res->abuse['country'], 'US');
                $this->assertEquals($res->abuse['email'], 'network-abuse@google.com');
                $this->assertEquals($res->abuse['name'], 'Abuse');
                $this->assertEquals($res->abuse['network'], '8.8.8.0/24');
                $this->assertEquals($res->abuse['phone'], '+1-650-253-0000');
            }
            if ($res->domains !== null) {
                $this->assertEquals($res->domains['ip'], '8.8.8.8');
            }
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
            $ipV4 = $res['4.4.4.4'];
            $this->assertEquals($ipV4['ip'], '4.4.4.4');
            $this->assertEquals($ipV4['city'], 'Paris');
            $this->assertEquals($ipV4['region'], 'Île-de-France');
            $this->assertEquals($ipV4['country'], 'FR');
            $this->assertEquals($ipV4['loc'], '48.8534,2.3488');
            $this->assertEquals($ipV4['postal'], '75000');
            $this->assertEquals($ipV4['timezone'], 'Europe/Paris');
            $this->assertEquals($ipV4['org'], 'AS3356 Level 3 Parent, LLC');
        }
    }

    public function testNetworkDetails()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        $h = new IPinfo($tok);
        $res = $h->getDetails('AS123');

        $this->assertEquals($res->asn, 'AS123');
        $this->assertEquals($res->name, 'Air Force Systems Networking');
        $this->assertEquals($res->country, 'US');
        $this->assertEquals($res->allocated, '1987-08-24');
        $this->assertEquals($res->registry, 'arin');
        $this->assertEquals($res->domain, 'af.mil');
        $this->assertEquals($res->num_ips, 0);
        $this->assertEquals($res->type, 'inactive');
        $this->assertEquals($res->prefixes, []);
        $this->assertEquals($res->prefixes6, []);
        $this->assertEquals($res->peers, null);
        $this->assertEquals($res->upstreams, null);
        $this->assertEquals($res->downstreams, null);
    }

    public function testBogonLocal4()
    {
        $h = new IPinfo();
        $ip = "127.0.0.1";
        $res = $h->getDetails($ip);
        $this->assertEquals($res->ip, '127.0.0.1');
        $this->assertTrue($res->bogon);
    }

    public function testBogonLocal6()
    {
        $h = new IPinfo();
        $ip = "2002:7f00::";
        $res = $h->getDetails($ip);
        $this->assertEquals($res->ip, '2002:7f00::');
        $this->assertTrue($res->bogon);
    }

    public function testIpv6Details()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        $h = new IPinfo($tok);
        $ip = "2607:f8b0:4005:805::200e";

        // test multiple times for cache hits
        for ($i = 0; $i < 5; $i++) {
            $res = $h->getDetails($ip);
            $this->assertEquals($res->ip, '2607:f8b0:4005:805::200e');
            $this->assertEquals($res->city, 'San Jose');
            $this->assertEquals($res->region, 'California');
            $this->assertEquals($res->country, 'US');
            $this->assertEquals($res->loc, '37.3394,-121.8950');
            $this->assertEquals($res->postal, '95025');
            $this->assertEquals($res->timezone, 'America/Los_Angeles');
        }
    }

    public function testIPv6DifferentNotations()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        $h = new IPinfo($tok);

        // Base IPv6 address with leading zeros in the second group
        $standard_ip = "2607:00:4005:805::200e";
        $standard_result = $h->getDetails($standard_ip);
        $this->assertEquals($standard_result->ip, '2607:00:4005:805::200e');
        $this->assertEquals($standard_result->city, 'Langenburg');
        $this->assertEquals($standard_result->region, 'Saskatchewan');
        $this->assertEquals($standard_result->country, 'CA');
        $this->assertEquals($standard_result->loc, '50.8500,-101.7176');
        $this->assertEquals($standard_result->timezone, 'America/Regina');

        // Various notations of the same IPv6 address
        $variations = [
            "2607:0:4005:805::200e",        // Removed leading zeros in a second group
            "2607:0000:4005:805::200e",     // Full form with all zeros in the second group
            "2607:0:4005:805:0:0:0:200e",   // Expanded form without compressed zeros
            "2607:0:4005:805:0000:0000:0000:200e", // Full expanded form
            "2607:00:4005:805:0::200e",     // Partially expanded
            "2607:00:4005:805::200E",       // Uppercase hex digits
            "2607:00:4005:0805::200e"       // Leading zero in a fourth group
        ];

        foreach ($variations as $id => $ip) {
            // Test each variation
            try {
                $result = $h->getDetails($ip);
            }
            catch (\Exception $e) {
                $this->fail("Failed to get details for IP #$id: $ip. Exception: " . $e->getMessage());
            }

            $this->assertEquals($ip, $result->ip, "IP #$id should match the requested IP : $ip");
            // Location data should be identical
            $this->assertEquals($standard_result->city, $result->city, "City should match for IP: $ip");
            $this->assertEquals($standard_result->region, $result->region, "Region should match for IP: $ip");
            $this->assertEquals($standard_result->country, $result->country, "Country should match for IP: $ip");
            $this->assertEquals($standard_result->loc, $result->loc, "Location should match for IP: $ip");
            $this->assertEquals($standard_result->timezone, $result->timezone, "Timezone should match for IP: $ip");

            // Binary comparison ensures the IP addresses are functionally identical
            $this->assertEquals(
                inet_ntop(inet_pton($standard_ip)),
                inet_ntop(inet_pton($result->ip)),
                "Normalized binary representation should match for IP: $ip"
            );
        }
    }

    public function testIPv6NotationsCaching()
    {
        $tok = getenv('IPINFO_TOKEN');
        if (!$tok) {
            $this->markTestSkipped('IPINFO_TOKEN env var required');
        }

        // Create IPinfo instance with custom cache size
        $h = new IPinfo($tok, ['cache_maxsize' => 10]);

        // Standard IPv6 address
        $standard_ip = "2607:f8b0:4005:805::200e";

        // Get details for standard IP (populate the cache)
        $standard_result = $h->getDetails($standard_ip);

        // Create a mock for the Guzzle client to track API requests
        $mock_guzzle = $this->createMock(\GuzzleHttp\Client::class);

        // The request method should never be called when IP is in cache
        $mock_guzzle->expects($this->never())
            ->method('request');

        // Replace the real Guzzle client with our mock
        $reflectionClass = new \ReflectionClass($h);
        $reflectionProperty = $reflectionClass->getProperty('http_client');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($h, $mock_guzzle);

        // Different notations of the same IPv6 address
        $variations = [
            "2607:f8b0:4005:805:0:0:0:200e",        // Full form
            "2607:f8b0:4005:805:0000:0000:0000:200e", // Full form with leading zeros
            "2607:f8b0:4005:0805::200e",            // With leading zero in a group
            "2607:f8b0:4005:805:0::200e",           // Partially expanded
            "2607:F8B0:4005:805::200E",             // Uppercase notation
            inet_ntop(inet_pton($standard_ip))      // Normalized form
        ];

        // Check cache hits for each variation
        foreach ($variations as $ip) {
            try {
                // When requesting data for IP variations, API request should not occur
                // because we expect a cache hit (normalized IP should be the same)
                $result = $h->getDetails($ip);

                // Additionally, verify that data matches the original request
                $this->assertEquals($standard_result->city, $result->city, "City should match for IP: $ip");
                $this->assertEquals($standard_result->country, $result->country, "Country should match for IP: $ip");

                // Verify address normalization in binary representation
                $this->assertEquals(
                    inet_ntop(inet_pton($standard_ip)),
                    inet_ntop(inet_pton($ip)),
                    "Normalized binary representation should match for IP: $ip"
                );
            } catch (\Exception $e) {
                $this->fail("Cache hit failed for IP notation: $ip. Exception: " . $e->getMessage());
            }
        }

        // Directly check if the key exists in cache
        $h->getDetails($standard_ip);

        // The normalized IP should exist in cache
        $normalized_ip = inet_ntop(inet_pton($standard_ip));
        $h->getDetails($normalized_ip);
    }
}
