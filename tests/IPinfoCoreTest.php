<?php

namespace ipinfo\ipinfo\tests;

use ipinfo\ipinfo\IPinfoCore;
use ipinfo\ipinfo\IPinfoException;
use PHPUnit\Framework\TestCase;

class IPinfoCoreTest extends TestCase
{
    public function testAccessToken()
    {
        $tok = "123";
        $client = new IPinfoCore($tok);
        $this->assertSame($tok, $client->access_token);
    }

    public function testDefaultCountries()
    {
        $client = new IPinfoCore();
        $this->assertSame("United States", $client->countries["US"]);
        $this->assertSame("France", $client->countries["FR"]);
    }

    public function testCustomCache()
    {
        $tok = "this is a fake access token";
        $cache = "this is a fake cache";
        $client = new IPinfoCore($tok, ["cache" => $cache]);
        $this->assertSame($cache, $client->cache);
    }

    public function testDefaultCacheSettings()
    {
        $client = new IPinfoCore();
        $this->assertSame(IPinfoCore::CACHE_MAXSIZE, $client->cache->maxsize);
        $this->assertSame(IPinfoCore::CACHE_TTL, $client->cache->ttl);
    }

    public function testCustomCacheSettings()
    {
        $tok = "this is a fake access token";
        $settings = ["cache_maxsize" => 100, "cache_ttl" => 11];
        $client = new IPinfoCore($tok, $settings);
        $this->assertSame($settings["cache_maxsize"], $client->cache->maxsize);
        $this->assertSame($settings["cache_ttl"], $client->cache->ttl);
    }

    public function testFormatDetailsObject()
    {
        $test_details = [
            "ip" => "8.8.8.8",
            "geo" => [
                "city" => "Mountain View",
                "region" => "California",
                "country" => "United States",
                "country_code" => "US",
            ],
            "as" => [
                "asn" => "AS15169",
                "name" => "Google LLC",
                "domain" => "google.com",
                "type" => "hosting",
            ],
            "is_anycast" => true,
            "is_hosting" => true,
        ];

        $h = new IPinfoCore();
        $res = $h->formatDetailsObject($test_details);

        $this->assertEquals("8.8.8.8", $res->ip);
        $this->assertEquals("Mountain View", $res->geo->city);
        $this->assertEquals("United States", $res->geo->country);
        $this->assertEquals("United States", $res->geo->country_name);
        $this->assertEquals("US", $res->geo->country_code);
        $this->assertEquals("🇺🇸", $res->geo->country_flag["emoji"]);
        $this->assertEquals(
            "https://cdn.ipinfo.io/static/images/countries-flags/US.svg",
            $res->geo->country_flag_url
        );
        $this->assertEquals("U+1F1FA U+1F1F8", $res->geo->country_flag["unicode"]);

        $this->assertEquals("AS15169", $res->asn->asn);
        $this->assertEquals("Google LLC", $res->asn->name);
        $this->assertEquals("google.com", $res->asn->domain);
        $this->assertEquals("hosting", $res->asn->type);

        $this->assertTrue($res->is_anycast);
        $this->assertTrue($res->is_hosting);
    }

    public function testBadIP()
    {
        $ip = "fake_ip";
        $h = new IPinfoCore();
        $this->expectException(IPinfoException::class);
        $h->getDetails($ip);
    }

    public function testLookupMe()
    {
        $tok = getenv("IPINFO_TOKEN");
        if (!$tok) {
            $this->markTestSkipped("IPINFO_TOKEN env var required");
        }

        $h = new IPinfoCore($tok);
        $res = $h->getDetails();

        // We can't know the actual values, we just need to check they're set
        $this->assertNotNull($res->ip);
        $this->assertNotNull($res->geo);
        $this->assertNotNull($res->geo->country_code);
        $this->assertNotNull($res->geo->country);
        $this->assertNotNull($res->geo->country_name);
        $this->assertIsBool($res->geo->is_eu);
        $this->assertNotNull($res->geo->country_flag);
        $this->assertNotNull($res->geo->country_flag_url);
        $this->assertNotNull($res->geo->country_currency);
        $this->assertNotNull($res->geo->continent_info);

        $this->assertNotNull($res->asn);
        $this->assertNotNull($res->asn->asn);
        $this->assertNotNull($res->asn->name);
        $this->assertNotNull($res->asn->domain);

        $this->assertIsBool($res->is_anonymous);
        $this->assertIsBool($res->is_anycast);
        $this->assertIsBool($res->is_hosting);
        $this->assertIsBool($res->is_mobile);
        $this->assertIsBool($res->is_satellite);
    }

    public function testLookup()
    {
        $tok = getenv("IPINFO_TOKEN");
        if (!$tok) {
            $this->markTestSkipped("IPINFO_TOKEN env var required");
        }

        $h = new IPinfoCore($tok);
        $res = $h->getDetails("8.8.8.8");

        $this->assertSame("8.8.8.8", $res->ip);
        $this->assertEquals("Mountain View", $res->geo->city);
        $this->assertEquals("California", $res->geo->region);
        $this->assertEquals("CA", $res->geo->region_code);
        $this->assertEquals("United States", $res->geo->country);
        $this->assertEquals("US", $res->geo->country_code);
        $this->assertEquals("North America", $res->geo->continent);
        $this->assertEquals("NA", $res->geo->continent_code);
        $this->assertIsFloat($res->geo->latitude);
        $this->assertIsFloat($res->geo->longitude);
        $this->assertEquals("America/Los_Angeles", $res->geo->timezone);
        $this->assertEquals("94043", $res->geo->postal_code);

        // Enriched fields
        $this->assertEquals("United States", $res->geo->country_name);
        $this->assertFalse($res->geo->is_eu);
        $this->assertEquals("🇺🇸", $res->geo->country_flag["emoji"]);
        $this->assertEquals("U+1F1FA U+1F1F8", $res->geo->country_flag["unicode"]);
        $this->assertEquals(
            "https://cdn.ipinfo.io/static/images/countries-flags/US.svg",
            $res->geo->country_flag_url
        );
        $this->assertEquals("USD", $res->geo->country_currency["code"]);
        $this->assertEquals("$", $res->geo->country_currency["symbol"]);
        $this->assertEquals("NA", $res->geo->continent_info["code"]);
        $this->assertEquals("North America", $res->geo->continent_info["name"]);

        $this->assertEquals("AS15169", $res->asn->asn);
        $this->assertEquals("Google LLC", $res->asn->name);
        $this->assertEquals("google.com", $res->asn->domain);
        $this->assertEquals("hosting", $res->asn->type);

        $this->assertFalse($res->is_anonymous);
        $this->assertTrue($res->is_anycast);
        $this->assertTrue($res->is_hosting);
        $this->assertFalse($res->is_mobile);
        $this->assertFalse($res->is_satellite);
    }

    public function testBogon()
    {
        $tok = getenv("IPINFO_TOKEN");
        if (!$tok) {
            $this->markTestSkipped("IPINFO_TOKEN env var required");
        }

        $h = new IPinfoCore($tok);
        $res = $h->getDetails("127.0.0.1");

        $this->assertEquals("127.0.0.1", $res->ip);
        $this->assertTrue($res->bogon);
    }
}
