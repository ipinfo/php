<?php

namespace jhtimmins\ipinfo\cache;

require_once(__DIR__ . '/Interface.php');

class DefaultCache implements CacheInterface {

  private $cache;
  private $element_queue;
  private $maxsize;
  private $ttl;

  public function __construct(int $maxsize, int $ttl)
  {
    $this->cache = new \Sabre\Cache\Memory();
    $this->element_queue;
    $this->maxsize = $maxsize;
    $this->ttl = $ttl;
  }

  public function has(string $name)
  {
    return $this->cache->has($name);
  }

  public function set(string $name, $value)
  {
      if (!$this->cache->has($name)) {
        $this->element_queue[] = $name;
      }

      $this->cache->set($name, $value, $this->ttl);

      $this->manageSize();
  }

  public function get(string $name)
  {
    return $this->cache->get($name);
  }

  private function manageSize()
  {
    $overflow = count($this->element_queue) - $this->maxsize;
    if ($overflow > 0) {
        foreach (array_slice($this->element_queue, 0, $overflow) as $name) {
          if ($this->cache->has($name)) {
            $this->cache->delete($name);
          }
        }
        $this->element_queue = array_slice($this->element_queue, $overflow);
    }
  }
}
