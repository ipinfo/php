<?php

namespace ipinfo\ipinfo\tests;

use ipinfo\ipinfo\cache\DefaultCache;
use PHPUnit\Framework\TestCase;

class DefaultCacheTest extends TestCase
{
    public function testHasValue()
    {
        $cache = new DefaultCache($maxsize = 4, $ttl = 2);
        $key1 = 'test';
        $value1 = 'obama';
        $cache->set($key1, $value1);

        $key2 = 'test2';
        $value2 = 'mccain';
        $cache->set($key2, $value2);

        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache->has($key2));
    }

    public function testDoesNotHaveValue()
    {
        $cache = new DefaultCache($maxsize = 4, $ttl = 2);
        $key = 'test';

        $this->assertFalse($cache->has($key));
    }

    public function testGetValue()
    {
        $cache = new DefaultCache($maxsize = 4, $ttl = 2);
        $key1 = 'test';
        $value1 = 'obama';
        $cache->set($key1, $value1);

        $key2 = 'test2';
        $value2 = 'mccain';
        $cache->set($key2, $value2);

        $this->assertEquals($value1, $cache->get($key1));
        $this->assertEquals($value2, $cache->get($key2));
    }

    public function testMaxSizeExceeded()
    {
        $cache = new DefaultCache($maxsize = 2, $ttl = 2);

        $key1 = 'test';
        $value1 = 'obama';
        $cache->set($key1, $value1);
        $this->assertEquals($value1, $cache->get($key1));

        $key2 = 'test2';
        $value2 = 'mccain';
        $cache->set($key2, $value2);
        $this->assertEquals($value2, $cache->get($key2));

        // Test that once the maxsize is exceeded, the earliest item is pushed out.
        $key3 = 'test3';
        $value3 = 'gore';
        $cache->set($key3, $value3);
        $this->assertEquals(null, $cache->get($key1));
        $this->assertEquals($value2, $cache->get($key2));
        $this->assertEquals($value3, $cache->get($key3));
    }

    public function testTimeToLiveExceeded()
    {
        $cache = new DefaultCache($maxsize = 2, $ttl = 1);

        $key = 'test';
        $value = 'obama';
        $cache->set($key, $value);
        $this->assertEquals($value, $cache->get($key));

        // Let the TTL expire.
        sleep(2);
        $this->assertEquals(null, $cache->get($key));
    }

    public function testCacheWithIPv6DifferentNotations()
    {
        // Create cache instance
        $cache = new DefaultCache(10, 600);

        // Original IPv6 address
        $standard_ip = "2607:f8b0:4005:805::200e";
        $standard_value = "standard_value";
        $cache->set($standard_ip, $standard_value);

        // Variations with zeros
        $variations = [
            "2607:f8b0:4005:805:0:0:0:200e", // Full form
            "2607:f8b0:4005:805:0000:0000:0000:200e", // Full form with leading zeros
            "2607:f8b0:4005:805:0000:00:00:200e", // Full form with few leading zeros
            "2607:F8B0:4005:805::200E", // Uppercase notation
            "2607:f8b0:4005:0805::200e", // Leading zero in a group
            "2607:f8b0:4005:805:0::200e", // Partially expanded
            "2607:f8b0:4005:805:0000::200e", // Full zeros in a second group
        ];

        // DefaultCache does normalize IPs, so we need to check if the cache has the same value
        foreach ($variations as $ip) {
            $this->assertTrue($cache->has($ip), "Cache should have variation: $ip");
        }
    }

    public function testDefaultCacheWithIPv4AndPostfixes()
    {
        // Create cache
        $cache = new DefaultCache(5, 600);

        // Test IPv4 with various postfixes
        $ipv4 = '8.8.8.8';
        $postfixes = ['_v1', '_latest', '_beta', '_test_123'];

        foreach ($postfixes as $postfix) {
            $key = $ipv4 . $postfix;
            $value = "value_for_$key";

            // Set value with postfix
            $cache->set($key, $value);

            // Verify it's in cache
            $this->assertTrue($cache->has($key), "Cache should have key with postfix: $key");
            $this->assertEquals($value, $cache->get($key), "Should get correct value for key with postfix");

            // Verify base IP is not affected
            if (!$cache->has($ipv4)) {
                $cache->set($ipv4, "base_ip_value");
            }

            $this->assertNotEquals($cache->get($ipv4), $cache->get($key), "Base IP and IP with postfix should have different values");
        }

        // Check all keys are still available (capacity not exceeded)
        foreach ($postfixes as $postfix) {
            $this->assertTrue($cache->has($ipv4 . $postfix), "All postfix keys should be available");
        }
        $this->assertTrue($cache->has($ipv4), "Base IP should be available");
    }

    public function testCacheWithIPv6AndPostfixes()
    {
        // Create cache
        $cache = new DefaultCache(5, 600);

        // Test IPv6 with various postfixes
        $ipv6 = '2607:f8b0:4005:805::200e';
        $postfixes = ['_v1', '_latest', '_beta', '_test_123'];

        foreach ($postfixes as $postfix) {
            $key = $ipv6 . $postfix;
            $value = "value_for_$key";

            // Set value with postfix
            $cache->set($key, $value);

            // Verify it's in cache
            $this->assertTrue($cache->has($key), "Cache should have key with postfix: $key");
            $this->assertEquals($value, $cache->get($key), "Should get correct value for key with postfix");
        }

        // Add the base IP to cache if not present
        if (!$cache->has($ipv6)) {
            $cache->set($ipv6, "base_ip_value");
        }

        // Ensure all keys are distinct and have different values
        $this->assertEquals("base_ip_value", $cache->get($ipv6), "Base IP should have its own value");
        foreach ($postfixes as $postfix) {
            $key = $ipv6 . $postfix;
            $expected = "value_for_$key";
            $this->assertEquals($expected, $cache->get($key), "Each postfix should have its own value");
        }
    }

    public function testCacheWithIPv6NotationsAndPostfixes()
    {
        // Create cache instance
        $cache = new DefaultCache(100, 600);

        // Original IPv6 address
        $standard_ip = "2607:f8b0:4005:805::200e";
        $postfixes = ['_v1', '_latest', '_beta', '_test_123'];

        // Variations with zeros
        $variations = [
            "2607:f8b0:4005:805:0:0:0:200e", // Full form
            "2607:f8b0:4005:805:0000:0000:0000:200e", // Full form with leading zeros
            "2607:f8b0:4005:805:0000:00:00:200e", // Full form with few leading zeros
            "2607:F8B0:4005:805::200E", // Uppercase notation
            "2607:f8b0:4005:0805::200e", // Leading zero in a group
            "2607:f8b0:4005:805:0::200e", // Partially expanded
            "2607:f8b0:4005:805:0000::200e", // Full zeros in a second group
        ];

        foreach ($postfixes as $postfix) {
            // Set cache with first postfix
            $value = "value_for_$standard_ip";
            $cache->set($standard_ip . $postfix, $value);
            foreach ($variations as $variation_id => $ip) {
                $key = $ip . $postfix;
                $this->assertTrue($cache->has($key), "Cache should have variation #$variation_id with postfix: $key");
                $this->assertEquals($value, $cache->get($key), "Should get correct value for key with postfix");
            }
        }

        // Check that the base IP not cached
        $this->assertFalse($cache->has($standard_ip), "Base IP should not be cached");
    }
}
