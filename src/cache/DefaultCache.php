<?php

namespace ipinfo\ipinfo\cache;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;
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
        $this->cache = new FilesystemAdapter();
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
        return $this->cache->hasItem($name);
    }

    public function delete(string $name): bool
    {
        return $this->$cache->delete($name);
    }

  /**
   * Set the IP address key to the specified value.
   * @param string $ip_address IP address to cache data for.
   * @param mixed $value Data for specified IP address.
   */
    public function set(string $name, $value)
    {
        // The callable will only be executed on a cache miss.
        $this->$cache->get($name, function (ItemInterface $item): string {
            $item->expiresAfter($this->ttl);
            // On cache miss update queue.
            $this->$element_queue[] = $name;
            
            return $value;
        });

        $this->manageSize();
    }

  /**
   * Get data for the specified IP address.
   * @param  string $ip_address IP address to lookup in cache.
   * @return mixed IP address data.
   */
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        return $this->cache->get($name);
    }

  /**
   * If cache maxsize has been reached, remove oldest elements until limit is reached.
   */
    private function manageSize()
    {
        $overflow = count($this->element_queue) - $this->maxsize;
        if ($overflow > 0) {
            foreach (array_slice($this->element_queue, 0, $overflow) as $name) {
                if ($this->cache->get($name)) {
                    $this->cache->delete($name);
                }
            }
            $this->element_queue = array_slice($this->element_queue, $overflow);
        }
    }
}
