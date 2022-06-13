PHP client for DIDWW API v3.
[![PHP from Packagist](https://img.shields.io/packagist/php-v/didww/didww-api-3-php-sdk.svg)](https://packagist.org/packages/didww/didww-api-3-php-sdk)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/didww/didww-api-3-php-sdk.svg)](https://packagist.org/packages/didww/didww-api-3-php-sdk)
![Tests](https://github.com/didww/didww-api-3-php-sdk/workflows/Tests/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/didww/didww-api-3-php-sdk/badge.svg?branch=master)](https://coveralls.io/github/didww/didww-api-3-php-sdk?branch=master)

About DIDWW API v3
-----

The DIDWW API provides a simple yet powerful interface that allows you to fully integrate your own applications with DIDWW services. An extensive set of actions may be performed using this API, such as ordering and configuring phone numbers, setting capacity, creating SIP trunks and retrieving CDRs and other operational data.

The DIDWW API v3 is a fully compliant implementation of the [JSON API specification](http://jsonapi.org/format/).

Read more https://doc.didww.com/api

## Installation

Execute command

```sh
composer require didww/didww-api-3-php-sdk
```

## Usage

```php

$credentials = new \Didww\Credentials('PLACEYOURAPIKEYHERE', 'sandbox');

\Didww\Configuration::configure($credentials);

$didGroup = \Didww\Item\DidGroup::all(['include'=>'stock_keeping_units', 'filter' => ['area_name' => 'Acapulco']])->getData()[0];
var_dump($didGroup->getAttributes());
```

For more examples visit https://github.com/didww/didww-api-3-php-sdk/tree/master/examples

For details on obtaining your API key please visit https://doc.didww.com/api#introduction-api-keys

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/didww/didww-api-3-php-sdk

## License

The package is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).

check test status
