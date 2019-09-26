<?php

namespace ipinfo\ipinfo\cache;

/**
 * Interface for caches used to store IP data between requests.
 */
interface CacheInterface {

  /**
   * Tests if the specified IP address is cached.
   * @param  string  $ip_address IP address to lookup.
   * @return boolean Is the IP address data in the cache.
   */
  public function has(string $ip_address);

  /**
   * Set the IP address key to the specified value.
   * @param string $ip_address IP address to cache data for.
   * @param mixed $value Data for specified IP address.
   */
  public function set(string $ip_address, $value);

  /**
   * Get data for the specied IP address.
   * @param  string $ip_address IP address to lookup in cache.
   * @return mixed IP address data.
   */
  public function get(string $ip_address);
}
