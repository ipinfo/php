<?php

namespace ipinfo\ipinfo;

/**
 * Holds formatted data received from Lite API for a single IP address.
 */
class DetailsLite
{
    public $ip;
    public $asn;
    public $as_name;
    public $as_domain;
    public $country_code;
    public $country;
    public $continent_code;
    public $continent;
    public $country_name;
    public $is_eu;
    public $country_flag;
    public $country_flag_url;
    public $country_currency;
    public $bogon;
    public $all;

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
    public function __toString(): string
    {
        return json_encode($this);
    }
}
