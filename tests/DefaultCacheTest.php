<?php

namespace ipinfo\ipinfo;

use ipinfo\ipinfo\cache\DefaultCache;
use ipinfo\ipinfo\Details;

class DefaultCacheTest extends \PHPUnit\Framework\TestCase
{
  public function testHasValue()
  {
    $cache = new DefaultCache($maxsize=4, $ttl=2);
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
    $cache = new DefaultCache($maxsize=4, $ttl=2);
    $key = 'test';

    $this->assertFalse($cache->has($key));
  }

  public function testGetValue()
  {
    $cache = new DefaultCache($maxsize=4, $ttl=2);
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
    $cache = new DefaultCache($maxsize=2, $ttl=2);

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
    $cache = new DefaultCache($maxsize=2, $ttl=1);

    $key = 'test';
    $value = 'obama';
    $cache->set($key, $value);
    $this->assertEquals($value, $cache->get($key));

    // Let the TTL expire.
    sleep(2);
    $this->assertEquals(null, $cache->get($key));
  }
}
