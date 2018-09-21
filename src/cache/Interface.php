<?php

namespace jhtimmins\ipinfo\cache;

interface CacheInterface {

  public function has(string $name);

  public function set(string $name, $value);

  public function get(string $name);
}
