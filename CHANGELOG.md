# CHANGELOG

### 2.1.0 (December 2 2020)

- Deprecate PHP 7.2 support.
- Add support for PHP 8.0.

### 2.0.0 (November 2020)

- A `guzzle_opts` option is supported in the settings, which allows full Guzzle
  option overrides.
- A `timeout` option is supported in the settings, which is the request timeout
  value, and defaults to 2 seconds.
  **BREAKING**: this was previously unconfigurable and was 0 seconds,
  i.e. infinite timeout.
- The `buildHeaders` method on the main `IPinfo` client is now private.
  **BREAKING**: this will no longer be available for use from the client.
- Only non-EOL PHP 7 versions are supported. In particular, PHP 7.2 and above
  are all supported and tested in the CI.
