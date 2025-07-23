# CHANGELOG

### 3.2.0

-   Fixes PHP8.0 support, add symfony/cache ^7.0 support, phpunit 11,12 support
-   Run tests on PHP 8.3 and 8.4
-   Fix ipv6 notations and add tests
-   Add support for IPinfo Lite API

### 3.1.4

-   No-op release.

### 3.1.3

-   No-op release.

### 3.1.2

-   Fixed cache key.

### 3.1.1

-   Patched deprecations.

### 3.1.0

-   Default cache changed. Replaced `sabre/cache` with `symfony/cache`.

### 3.0.1

-   Added the link of country flag image.

### 3.0.0

-   Require PHP >= 8.0.
-   Add local bogon checking.

### 2.3.1

-   Added Stringable implementation for PHP 8.0 with backward compatibility.

### 2.3.0

-   Added batch ops integration.
-   Added the ability to disable cache usage entirely.

### 2.2.0

-   Added Maps integration.
-   Added versioned cache keys.
    This allows more reliable changes to cached data in the future without
    causing confusing incompatibilities. This should be transparent to the user.
    This is primarily useful for users with persistent cache implementations.

### 2.1.1

-   Bug fix issue reported in Laravel SDK
    (https://github.com/ipinfo/laravel/issues/14) which also applies in PHP SDK,
    with https://github.com/ipinfo/php/pull/27.

### 2.1.0

-   Deprecate PHP 7.2 support.
-   Add support for PHP 8.0.

### 2.0.0

-   A `guzzle_opts` option is supported in the settings, which allows full Guzzle
    option overrides.
-   A `timeout` option is supported in the settings, which is the request timeout
    value, and defaults to 2 seconds.
    **BREAKING**: this was previously unconfigurable and was 0 seconds,
    i.e. infinite timeout.
-   The `buildHeaders` method on the main `IPinfo` client is now private.
    **BREAKING**: this will no longer be available for use from the client.
-   Only non-EOL PHP 7 versions are supported. In particular, PHP 7.2 and above
    are all supported and tested in the CI.
