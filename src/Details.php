<?php

namespace ipinfo\ipinfo;

/**
 * Holds formatted data for a single IP address.
 */
class Details
{
  public function __construct($raw_details)
  {
    foreach ($raw_details as $property => $value) {
      $this->$property = $value;
    }
    $this->all = $raw_details;
  }
}
