<?php

namespace ipinfo\ipinfo;

/**
 * Holds formatted data for a single IP address.
 */
#[\AllowDynamicProperties]
class Details
{
    public $country;
    public $country_name;
    public $country_flag;
    public $country_code;
    public $country_flag_url;
    public $country_currency;
    public $continent;
    public $latitude;
    public $longitude;
    public $loc;
    public $is_eu;
    public $ip;
    public $hostname;
    public $anycast;
    public $city;
    public $org;
    public $postal;
    public $region;
    public $timezone;
    public $asn;
    public $company;
    public $privacy;
    public $abuse;
    public $domains;
    public $bogon;
    public $is_anycast;
    public $is_mobile;
    public $is_anonymous;
    public $is_satellite;
    public $is_hosting;
    public $name;
    public $allocated;
    public $registry;
    public $domain;
    public $num_ips;
    public $type;
    public $prefixes;
    public $prefixes6;
    public $peers;
    public $upstreams;
    public $downstreams;
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
    public function __toString(): string {
        return json_encode($this);
    }
}
