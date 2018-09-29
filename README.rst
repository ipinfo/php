The official PHP library for the `IPinfo <https://ipinfo.io/>`_ API.
###########################################################################

A lightweight wrapper for the IPinfo API, which provides up-to-date IP address data.

.. contents::

.. section-numbering::


Installation
=====
>>> composer require ipinfo/ipinfo

Usage
=====

The `IPinfo->getDetails()` method accepts an IP address as an optional, positional argument. If no IP address is specified, the API will return data for the IP address from which it receives the request.

>>> $access_token = '123456789abc';
>>> $client = new IPinfo($access_token);
>>> $ip_address = '216.239.36.21';
>>> $details = $client->getDetails($ip_address);
>>> $detail->city;
Emeryville
>>> $details->loc;
37.8342,-122.2900

Authentication
==============
The IPinfo library can be authenticated with your IPinfo API token, which is passed in as a positional argument. It also works without an authentication token, but in a more limited capacity.

>>> $access_token = '123456789abc';
>>> $client = new IPinfo($access_token);


Details Data
=============
`IPinfo->getDetails()` will return a `Details` object that contains all fields listed `IPinfo developer docs <https://ipinfo.io/developers/responses#full-response>`_ with a few minor additions. Properties can be accessed directly.

>>> $details->hostname;
cpe-104-175-221-247.socal.res.rr.com


Country Name
------------

`Details->country_name` will return the country name, as supplied by the `countries.json` file. See below for instructions on changing that file for use with non-English languages. `Details->country` will still return country code.

>>> $details->country;
US
>>> $details->country_name;
United States


Longitude and Latitude
----------------------

`Details->latitude` and `Details->longitude` will return latitude and longitude, respectively, as strings. `Details->loc` will still return a composite string of both values.

>>> $details->loc;
34.0293,-118.3570
>>> $details->latitude;
34.0293
>>> $details->longitude;
-118.3570

Accessing all properties
------------------------

`Details->all` will return all details data as a dictionary.

>>> $details->all;
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

Caching
=======
In-memory caching of `Details` data is provided by default via the `sabre/cache <https://github.com/sabre-io/cache/>`_ library. LRU (least recently used) cache-invalidation functionality has been added to the default TTL (time to live). This means that values will be cached for the specified duration; if the cache's max size is reached, cache values will be invalidated as necessary, starting with the oldest cached value.

Modifying cache options
-----------------------

Default cache TTL and maximum size can be changed by setting values in the `$settings` argument array. 

* Default maximum cache size: 4096 (multiples of 2 are recommended to increase efficiency)
* Default TTL: 24 hours (in seconds)

>>> $access_token = '123456789abc';
>>> $settings = ['cache_maxsize' => 30, 'cache_ttl' => 128];
>>> $client = new IPinfo($access_token, $settings);

Using a different cache
-----------------------

It's possible to use a custom cache by creating a child class of the `CacheInterface <>`_ class and passing this into the handler object with the `cache` keyword argument. FYI this is known as `the Strategy Pattern <https://sourcemaking.com/design_patterns/strategy>`_.


>>> $access_token = '123456789abc';
>>> $settings = ['cache' => $my_fancy_custom_cache];
>>> $client = new IPinfo($access_token, $settings);


Internationalization
====================
When looking up an IP address, the response object includes a `Details->country_name` attribute which includes the country name based on American English. It is possible to return the country name in other languages by setting the `countries_file` keyword argument when creating the `IPinfo` object.

The file must be a `.json` file with the following structure::

    {
     "BD": "Bangladesh",
     "BE": "Belgium",
     "BF": "Burkina Faso",
     "BG": "Bulgaria"
     ...
    }
