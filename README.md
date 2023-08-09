# [<img src="https://ipinfo.io/static/ipinfo-small.svg" alt="IPinfo" width="24"/>](https://ipinfo.io/) IPinfo PHP Client Library

This is the official PHP client library for the [IPinfo.io](https://ipinfo.io) IP address API, allowing you to look up your own IP address, or get any of the following details for an IP:
 - [IP to Geolocation data](https://ipinfo.io/ip-geolocation-api) (city, region, country, postal code, latitude, and longitude)
 - [ASN information](https://ipinfo.io/asn-api) (ISP or network operator, associated domain name, and type, such as business, hosting, or company)
 - [Company details](https://ipinfo.io/ip-company-api) (the name and domain of the business that uses the IP address)
 - [Carrier information](https://ipinfo.io/ip-carrier-api) (the name of the mobile carrier and MNC and MCC for that carrier if the IP is used exclusively for mobile traffic)

Check all the data we have for your IP address [here](https://ipinfo.io/what-is-my-ip).

### Getting Started

You'll need an IPinfo API access token, which you can get by signing up for a free account at [https://ipinfo.io/signup](https://ipinfo.io/signup?ref=lib-PHP).

The free plan is limited to 50,000 requests per month, and doesn't include some of the data fields such as IP type and company data. To enable all the data fields and additional request volumes see [https://ipinfo.io/pricing](https://ipinfo.io/pricing?ref=lib-PHP).

#### Installation

The package works with PHP 8 and is available using [Composer](https://getcomposer.org).

```shell
composer require ipinfo/ipinfo
``` 

#### Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ipinfo\ipinfo\IPinfo;

$access_token = '123456789abc';
$client = new IPinfo($access_token);
$ip_address = '216.239.36.21';
$details = $client->getDetails($ip_address);

$details->city; // Emeryville
$details->loc; // 37.8342,-122.2900
```

### Usage

The `IPinfo->getDetails()` method accepts an IP address as an optional, positional argument. If no IP address is specified, the API will return data for the IP address from which it receives the request.

```php
$client = new IPinfo();
$ip_address = '216.239.36.21';
$details = $client->getDetails($ip_address);
$details->city; // Emeryville
$details->loc; // 37.8342,-122.2900
```

### Authentication

The IPinfo library can be authenticated with your IPinfo API token, which is passed in as a positional argument. It also works without an authentication token, but in a more limited capacity.

```php
$access_token = '123456789abc';
$client = new IPinfo($access_token);
```

### Details Data

`IPinfo->getDetails()` will return a `Details` object that contains all fields listed [IPinfo developer docs](https://ipinfo.io/developers/responses#full-response) with a few minor additions. Properties can be accessed directly.

```php
$details->hostname; // cpe-104-175-221-247.socal.res.rr.com
```

#### Country Name

`Details->country_name` will return the country name, as supplied by the `countries.json` file. See below for instructions on changing that file for use with non-English languages. `Details->country` will still return the country code.

```php
$details->country; // US
$details->country_name; // United States
```

#### EU Country

`Details->is_eu` will return true if the country is a member of the EU, as supplied by the `eu.json` file.

```php
$details->is_eu; // False
```

#### Country Flag

`Details->country_flag` will return the emoji and Unicode representations of
the country's flag, as supplied by the `flags.json` file.

```php
$details->country_flag['emoji']; // 🇺🇸
$details->country_flag['unicode']; // U+1F1FA U+1F1F8
```

#### Country Flag URL

`Details->country_flag_url` will return a public link to the country's flag image as an SVG which can be used anywhere.

```php
$details->country_flag_url; // https://cdn.ipinfo.io/static/images/countries-flags/US.svg
```

#### Country Currency

`Details->country_currency` will return the code and symbol of the 
country's currency, as supplied by the `currency.json` file.

```php
$details->country_currency['code']; // USD
$details->country_currency['symbol']; // $
```

#### Continent

`Details->continent` will return the code and name of the 
continent, as supplied by the `continent.json` file.

```php
$details->continent['code']; // NA
$details->continent['name']; // North America
```

#### Longitude and Latitude

`Details->latitude` and `Details->longitude` will return latitude and longitude, respectively, as strings. `Details->loc` will still return a composite string of both values.

```php
$details->loc; // 34.0293,-118.3570
$details->latitude; // 34.0293
$details->longitude; // -118.3570
```

#### Accessing all properties

`Details->all` will return all details data as a dictionary.

```php
$details->all;
/*
    {
    'asn': {  'asn': 'AS20001',
               'domain': 'twcable.com',
               'name': 'Time Warner Cable Internet LLC',
               'route': '104.172.0.0/14',
               'type': 'isp'},
    'city': 'Los Angeles',
    'company': {   'domain': 'twcable.com',
                   'name': 'Time Warner Cable Internet LLC',
                   'type': 'isp'},
    'country': 'US',
    'country_name': 'United States',
    'hostname': 'cpe-104-175-221-247.socal.res.rr.com',
    'ip': '104.175.221.247',
    'loc': '34.0293,-118.3570',
    'latitude': '34.0293',
    'longitude': '-118.3570',
    'phone': '323',
    'postal': '90016',
    'region': 'California'
    }
*/
```

### Caching

In-memory caching of `Details` data is provided by default via the [symfony/cache](https://github.com/symfony/cache/) library. LRU (least recently used) cache-invalidation functionality has been added to the default TTL (time to live). This means that values will be cached for the specified duration; if the cache's max size is reached, cache values will be invalidated as necessary, starting with the oldest cached value.

#### Modifying cache options

Default cache TTL and maximum size can be changed by setting values in the `$settings` argument array.

* Default maximum cache size: 4096 (multiples of 2 are recommended to increase efficiency)
* Default TTL: 24 hours (in seconds)

```php
$access_token = '123456789abc';
$settings = ['cache_maxsize' => 30, 'cache_ttl' => 128];
$client = new IPinfo($access_token, $settings);
```

#### Using a different cache

It's possible to use a custom cache by creating a child class of the [CacheInterface](https://github.com/ipinfo/php/blob/master/src/cache/Interface.php) class and passing this into the handler object with the `cache` keyword argument. FYI this is known as [the Strategy Pattern](https://sourcemaking.com/design_patterns/strategy).

```php
$access_token = '123456789abc';
$settings = ['cache' => $my_fancy_custom_cache];
$client = new IPinfo($access_token, $settings);
```

#### Disabling the cache

You may disable the cache by passing in a `cache_disabled` key in the settings:

```php
$access_token = '123456789abc';
$settings = ['cache_disabled' => true];
$client = new IPinfo($access_token, $settings);
```

### Overriding HTTP Client options

The IPinfo client constructor accepts a `timeout` key which is the request
timeout in seconds.

For full flexibility, a `guzzle_opts` key is accepted which accepts an
associative array which is described in [Guzzle Request Options](https://docs.guzzlephp.org/en/stable/request-options.html).
Options set here will override any custom settings set by the IPinfo client
internally in case of conflict, including headers.

### Batch Operations

Looking up a single IP at a time can be slow. It could be done concurrently from the client side, but IPinfo supports a batch endpoint to allow you to group together IPs and let us handle retrieving details for them in bulk for you.

```php
$access_token = '123456789abc';
$client = new IPinfo($access_token);
$ips = ['1.1.1.1', '8.8.8.8', '1.2.3.4/country'];
$results = $client->getBatchDetails($ips);
echo $results['1.2.3.4/country']; // AU
var_dump($results['1.1.1.1']);
var_dump($results['8.8.8.8']);
```

The input size is not limited, as the interface will chunk operations for you behind the scenes.

Please see [the official documentation](https://ipinfo.io/developers/batch) for more information and limitations.

### Internationalization

When looking up an IP address, the response object includes a `Details->country_name` attribute which includes the country name based on American English. It is possible to return the country name in other languages by setting the `countries_file` keyword argument when creating the `IPinfo` object.

The file must be a `.json` file with the following structure:

```JSON
{
 "BD": "Bangladesh",
 "BE": "Belgium",
 "BF": "Burkina Faso",
 "BG": "Bulgaria"
 ...
}
```

### Other Libraries

There are official IPinfo client libraries available for many languages including PHP, Python, Go, Java, Ruby, and many popular frameworks such as Django, Rails, and Laravel. There are also many third-party libraries and integrations available for our API.

### About IPinfo

Founded in 2013, IPinfo prides itself on being the most reliable, accurate, and in-depth source of IP address data available anywhere. We process terabytes of data to produce our custom IP geolocation, company, carrier, privacy, hosted domains and IP type data sets. Our API handles over 40 billion requests a month for 100,000 businesses and developers.

[![image](https://avatars3.githubusercontent.com/u/15721521?s=128&u=7bb7dde5c4991335fb234e68a30971944abc6bf3&v=4)](https://ipinfo.io/)
