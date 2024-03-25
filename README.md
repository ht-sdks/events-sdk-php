events-sdk-php
==============
[![CI](https://github.com/ht-sdks/events-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/ht-sdks/events-sdk-php/actions/workflows/ci.yml)

## Installation

The package can be installed via composer.

```sh
composer require ht-sdks/events-sdk-php
```

## Documentation

The links bellow should provide all the documentation needed to make the best use of the library and the Hightouch Events API:

- [Docs](https://hightouch.com/docs/events/sdks/php)
- [API](https://hightouch.com/docs/events/sdks/http)
- [Specs](https://hightouch.com/docs/events/event-spec)

## Usage

```php
use Hightouch\\Hightouch;

Hightouch::init('WRITE_KEY', [
  'host' => 'https://us-east-1.hightouch-events.com',
]);

Hightouch::track([
  'event' => 'Created Account',
  'userId' => '123',
  'properties' => [
    'application' => 'Desktop',
    'version' => '1.2.3',
  ],
]);
```

## License

This library is released under the [MIT License](LICENSE).
