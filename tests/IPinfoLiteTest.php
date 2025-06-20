<?php

namespace ipinfo\ipinfo\tests;

use ipinfo\ipinfo\IPinfoLite;
use ipinfo\ipinfo\IPinfoException;
use PHPUnit\Framework\TestCase;

class IPinfoLiteTest extends TestCase
{
    public function testAccessToken()
    {
        $tok = "123";
        $client = new IPinfoLite($tok);
        $this->assertSame($tok, $client->access_token);
    }

    public function testDefaultCountries()
    {
        $client = new IPinfoLite();
        $this->assertSame("United States", $client->countries["US"]);
        $this->assertSame("France", $client->countries["FR"]);
    }

    public function testCustomCache()
    {
        $tok = "this is a fake access token";
        $cache = "this is a fake cache";
        $client = new IPinfoLite($tok, ["cache" => $cache]);
        $this->assertSame($cache, $client->cache);
    }

    public function testDefaultCacheSettings()
    {
        $client = new IPinfoLite();
        $this->assertSame(IPinfoLite::CACHE_MAXSIZE, $client->cache->maxsize);
        $this->assertSame(IPinfoLite::CACHE_TTL, $client->cache->ttl);
    }

    public function testCustomCacheSettings()
    {
        $tok = "this is a fake access token";
        $settings = ["cache_maxsize" => 100, "cache_ttl" => 11];
        $client = new IPinfoLite($tok, $settings);
        $this->assertSame($settings["cache_maxsize"], $client->cache->maxsize);
        $this->assertSame($settings["cache_ttl"], $client->cache->ttl);
    }

    public function testFormatDetailsObject()
    {
        $test_details = [
            "country" => "United States",
            "country_code" => "US",
        ];

        $h = new IPinfoLite();
        $res = $h->formatDetailsObject($test_details);

        $this->assertEquals("United States", $res->country);
        $this->assertEquals("United States", $res->country_name);
        $this->assertEquals("US", $res->country_code);
        $this->assertEquals("🇺🇸", $res->country_flag["emoji"]);
        $this->assertEquals(
            "https://cdn.ipinfo.io/static/images/countries-flags/US.svg",
            $res->country_flag_url
        );
        $this->assertEquals("U+1F1FA U+1F1F8", $res->country_flag["unicode"]);
    }

    public function testBadIP()
    {
        $ip = "fake_ip";
        $h = new IPinfoLite();
        $this->expectException(IPinfoException::class);
        $h->getDetails($ip);
    }

    public function testLookupMe()
    {
        $tok = getenv("IPINFO_TOKEN");
        if (!$tok) {
            $this->markTestSkipped("IPINFO_TOKEN env var required");
        }

        $h = new IPinfoLite($tok);
        $res = $h->getDetails();

        // We can't know the actual values, we just need to check they're set
        $this->assertNotNull($res->ip);
        $this->assertNotNull($res->asn);
        $this->assertNotNull($res->as_name);
        $this->assertNotNull($res->as_domain);
        $this->assertNotNull($res->country_code);
        $this->assertNotNull($res->country);
        $this->assertNotNull($res->continent_code);
        $this->assertNotNull($res->continent);
        $this->assertNotNull($res->country_name);
        $this->assertNotNull($res->is_eu);
        $this->assertNotNull($res->country_flag);
        $this->assertNotNull($res->country_flag_url);
        $this->assertNotNull($res->country_currency);
        // Bogon must not be set
        $this->assertNull($res->bogon);

        $res = $h->getDetails("me");

        // We can't know the actual values, we just need to check they're set
        $this->assertNotNull($res->ip);
        $this->assertNotNull($res->asn);
        $this->assertNotNull($res->as_name);
        $this->assertNotNull($res->as_domain);
        $this->assertNotNull($res->country_code);
        $this->assertNotNull($res->country);
        $this->assertNotNull($res->continent_code);
        $this->assertNotNull($res->continent);
        $this->assertNotNull($res->country_name);
        $this->assertNotNull($res->is_eu);
        $this->assertNotNull($res->country_flag);
        $this->assertNotNull($res->country_flag_url);
        $this->assertNotNull($res->country_currency);
        // Bogon must not be set
        $this->assertNull($res->bogon);
    }

    public function testLookup()
    {
        $tok = getenv("IPINFO_TOKEN");
        if (!$tok) {
            $this->markTestSkipped("IPINFO_TOKEN env var required");
        }

        $h = new IPinfoLite($tok);
        $ip = "8.8.8.8";

        // test multiple times for cache hits
        for ($i = 0; $i < 5; $i++) {
            $res = $h->getDetails($ip);
            $this->assertEquals("8.8.8.8", $res->ip);
            $this->assertEquals("AS15169", $res->asn);
            $this->assertEquals("Google LLC", $res->as_name);
            $this->assertEquals("google.com", $res->as_domain);
            $this->assertEquals("US", $res->country_code);
            $this->assertEquals("United States", $res->country);
            $this->assertEquals("NA", $res->continent_code);
            $this->assertEquals("North America", $res->continent);
            $this->assertEquals("United States", $res->country_name);
            $this->assertEquals("", $res->is_eu);
            $this->assertEquals("🇺🇸", $res->country_flag["emoji"]);
            $this->assertEquals(
                "https://cdn.ipinfo.io/static/images/countries-flags/US.svg",
                $res->country_flag_url
            );
            $this->assertEquals(
                "U+1F1FA U+1F1F8",
                $res->country_flag["unicode"]
            );
            $this->assertEquals("USD", $res->country_currency["code"]);
            $this->assertEquals('$', $res->country_currency["symbol"]);
        }
    }

    public function testGuzzleOverride()
    {
        $tok = getenv("IPINFO_TOKEN");
        if (!$tok) {
            $this->markTestSkipped("IPINFO_TOKEN env var required");
        }

        $h = new IPinfoLite($tok, [
            "guzzle_opts" => [
                "headers" => [
                    "authorization" => "Bearer blah",
                ],
            ],
        ]);
        $ip = "8.8.8.8";

        $this->expectException(IPinfoException::class);
        $res = $h->getDetails($ip);
    }

    public function testBogonLocal4()
    {
        $h = new IPinfoLite();
        $ip = "127.0.0.1";
        $res = $h->getDetails($ip);
        $this->assertEquals($res->ip, "127.0.0.1");
        $this->assertTrue($res->bogon);
    }

    public function testBogonLocal6()
    {
        $h = new IPinfoLite();
        $ip = "2002:7f00::";
        $res = $h->getDetails($ip);
        $this->assertEquals($res->ip, "2002:7f00::");
        $this->assertTrue($res->bogon);
    }
}
