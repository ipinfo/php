<?php

namespace ipinfo\ipinfo\cache;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Default implementation of the CacheInterface. Provides in-memory caching.
 */
class DefaultCache implements CacheInterface
{

    public $maxsize;
    public $ttl;

    private $cache;
    private $element_queue;

    public function __construct(int $maxsize, int $ttl)
    {
        $this->cache = new ArrayAdapter();
        $this->element_queue = array();
        $this->maxsize = $maxsize;
        $this->ttl = $ttl;
    }

  /**
   * Tests if the specified IP address is cached.
   * @param  string  $ip_address IP address to lookup.
   * @return boolean Is the IP address data in the cache.
   */
    public function has(string $name): bool
    {
        $name = $this->sanitizeName($name);

        return $this->cache->hasItem($name);
    }

  /**
   * Set the IP address key to the specified value.
   * @param string $ip_address IP address to cache data for.
   * @param mixed $value Data for specified IP address.
   */
    public function set(string $name, $value)
    {
        $name = $this->sanitizeName($name);
        if (!$this->cache->hasItem($name)) {
            $this->element_queue[] = $name;
        }

        $this->cache->get($name, function (ItemInterface $item) use ($value) {
            $item->set($value)->expiresAfter($this->ttl);
            return $item->get();
        });

        $this->manageSize();
    }

  /**
   * Get data for the specified IP address.
   * @param  string $ip_address IP address to lookup in cache.
   * @return mixed IP address data.
   */
    public function get(string $ip_address)
    {
        $sanitizeName = $this->sanitizeName($ip_address);
        $result = $this->cache->getItem($sanitizeName)->get();
        if (is_array($result) && array_key_exists("ip", $result)) {
            /**
             * The IPv6 may have different notation and we don't know which one is cached.
             * We want to give the user the same notation as the one used in his request which may be different from
             * the one used in the cache.
             */
            $result["ip"] = $this->getIpAddress($ip_address);
        }

        return $result;
    }

  /**
   * If cache maxsize has been reached, remove oldest elements until limit is reached.
   */
    private function manageSize()
    {
        $overflow = count($this->element_queue) - $this->maxsize;
        if ($overflow > 0) {
            foreach (array_slice($this->element_queue, 0, $overflow) as $name) {
                if ($this->has($name)) {
                    $this->cache->delete($name);
                }
            }
            $this->element_queue = array_slice($this->element_queue, $overflow);
        }
    }

    private function getIpAddress(string $name): string
    {
        // The $name has the version postfix applied, we need to extract the IP address without it
        $parts = explode('_', $name);
        return $parts[0];
    }

   /**
    * Remove forbidden characters from cache keys
    */
    private function sanitizeName(string $name): string
    {
        // The $name has the version postfix applied, we need to extract the IP address without it
        $parts = explode('_', $name);
        $ip = $parts[0];
        try {
            // Attempt to normalize the IPv6 address
            $binary = @inet_pton($ip); // Convert to 16-byte binary
            if ($binary !== false && strlen($binary) === 16) { // Valid IPv6
                $ip = inet_ntop($binary); // Convert to full notation (e.g., 2001:0db8:...)
            }
            $name = $ip . '_' . implode('_', array_slice($parts, 1));
        } catch (\Exception) {
            // If invalid, proceed with original input
        }

        $forbiddenCharacters = str_split(CacheItem::RESERVED_CHARACTERS);
        return str_replace($forbiddenCharacters, '^', $name);
    }
}
