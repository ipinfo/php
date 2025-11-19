<?php

namespace ipinfo\ipinfo;

/**
 * Holds formatted data received from Core API for a single IP address.
 */
class DetailsCore
{
    public $ip;
    public $geo;
    public $asn;
    public $is_anonymous;
    public $is_anycast;
    public $is_hosting;
    public $is_mobile;
    public $is_satellite;
    public $bogon;
    public $all;

    public function __construct($raw_details)
    {
        foreach ($raw_details as $property => $value) {
            // Handle nested 'as' object - rename to 'asn' to avoid PHP keyword
            if ($property === 'as') {
                $this->asn = (object) $value;
            } elseif ($property === 'geo') {
                $this->geo = (object) $value;
            } else {
                $this->$property = $value;
            }
        }
        $this->all = $raw_details;
    }

    /**
     * Returns json string representation.
     *
     * @internal this class should implement Stringable explicitly when leaving support for PHP verision < 8.0
     */
    public function __toString(): string
    {
        return json_encode($this);
    }
}
