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

    /**
     * Returns json string representation.
     * 
     * @internal this class should implement Stringable explicitly when leaving support for PHP verision < 8.0
     */
    public function __toString(): string {
        return json_encode($this);
    }
}
