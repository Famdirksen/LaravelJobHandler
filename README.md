# LaravelJobHandler

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

This package is still in development.

## Install

Via Composer

``` bash
$ composer require famdirksen/laravel-job-handler
```

## Usage

``` php
$output = [];

$crawler = CrawlController::setupCrawler(MyJob::class);

$crawler->start();

//call your methods

$crawler->finish($output);
```

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email robin@famdirksen.nl instead of using the issue tracker.

## Credits

- [Robin Dirksen][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/famdirksen/laravel-job-handler.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Famdirksen/LaravelJobHandler/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/Famdirksen/LaravelJobHandler.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Famdirksen/LaravelJobHandler.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/famdirksen/laravel-job-handler.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/famdirksen/laravel-job-handler
[link-travis]: https://travis-ci.org/Famdirksen/LaravelJobHandler
[link-scrutinizer]: https://scrutinizer-ci.com/g/Famdirksen/LaravelJobHandler/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Famdirksen/LaravelJobHandler
[link-downloads]: https://packagist.org/packages/famdirksen/laravel-job-handler
[link-author]: https://github.com/robindirksen1
[link-contributors]: ../../contributors
