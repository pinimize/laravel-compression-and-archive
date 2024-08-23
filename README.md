# Pinimize

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pinimize/laravel-compression-and-archive.svg?style=flat-square)](https://packagist.org/packages/pinimize/laravel-compression-and-archive)
[![Total Downloads](https://img.shields.io/packagist/dt/pinimize/laravel-compression-and-archive.svg?style=flat-square)](https://packagist.org/packages/pinimize/laravel-compression-and-archive)
[![Tests](https://github.com/pinimize/laravel-compression-and-archive/actions/workflows/phpunit.yml/badge.svg?branch=main)](https://github.com/pinimize/laravel-compression-and-archive/actions/workflows/phpunit.yml)
[![License](https://img.shields.io/packagist/l/pinimize/laravel-compression-and-archive.svg?style=flat-square)](https://packagist.org/packages/pinimize/laravel-compression-and-archive)

Pinimize is a powerful Laravel package that simplifies file compression, decompression, archiving, and unarchiving operations. It provides a clean and intuitive API for handling various compression and archiving tasks in your Laravel applications, with full support for Laravel's Storage system.

<p align="center"><img src="/docs/logo.jpg" alt="Logo with brown western bar doors with western scene in background and text that says: Saloon, Your Lone Star of your API integrations"></p>

## Features

- File compression and decompression
- String compression and decompression
- Stream compression and decompression
- File archiving and unarchiving
- Support for multiple compression algorithms and archive formats
- Facade-based API for easy integration
- Full integration with Laravel's Storage system
- Extensible architecture with support for custom drivers
- Actively maintained and regularly updated
- Written in clean, modern PHP code

## Installation

You can install the package via composer:

```bash
composer require pinimize/laravel-compression-and-archive
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Pinimize\PinimizeServiceProvider" --tag="config"
```

This will create a `config/pinimize.php` file where you can configure the default drivers and their options.

## Usage

### Compression

```php
use Pinimize\Facades\Compression;

// Compress a string
$compressed = Compression::string('Hello, World!');

// Compress a file (local filesystem)
Compression::file('source.txt', 'compressed.gz', [
    'disk' => 'local',
]);

Compression::file('source.txt', 'compressed.gz', [
    'disk' => [
        'source' => 'local',
        'destination' => 's3',
    ]
]);

// Compress from a PHP resoruces
$resource = fopen('source.txt', 'r');
Compression::resource($resource, 'compressed.gz', 's3');

// Get compression ratio
$ratio = Compression::getRatio('source.txt', 'compressed.gz', 's3', 'local');
```

### Decompression

```php
use Pinimize\Facades\Decompression;

// Decompress a file (local filesystem)
Decompression::decompress('compressed.gz', 'decompressed.txt');

// Decompress a file using Storage disks
Decompression::decompress('compressed.gz', 'decompressed.txt', 's3', 'local');

// Decompress a string
$decompressed = Decompression::decompressString($compressedString);

// Decompress to a stream
$inputResource = fopen('compressed.gz', 'r');
$outputResource = fopen('decompressed.txt', 'w');
Decompression::decompressStream($inputResource, $outputResource);
```

### Archiving

```php
use Pinimize\Facades\Archive;

// Create an archive (local filesystem)
Archive::create(['file1.txt', 'file2.txt'], 'archive.zip');

// Create an archive using Storage disks
Archive::create(['file1.txt', 'file2.txt'], 'archive.zip', 's3', 'local');

// Add files to an existing archive
Archive::add('archive.zip', ['newfile.txt' => 'path/to/newfile.txt'], 'local', 's3');

// List contents of an archive
$contents = Archive::listContents('archive.zip', 's3');
```

### Unarchiving

```php
use Pinimize\Facades\Unarchive;

// Extract an archive (local filesystem)
Unarchive::extract('archive.zip', 'extracted_folder');

// Extract an archive using Storage disks
Unarchive::extract('archive.zip', 's3', 'extracted_folder',  'local');
```

### Downloading

```php
$archive = Archive::create(['file1.txt', 'file2.txt'], 'archive.zip');

$archive->download('archive.zip', $name, $headers);

$archive->url('archive.zip');
```

## Extending

You can easily add custom drivers by extending the respective manager classes:

```php
use Pinimize\Managers\CompressionManager;

CompressionManager::extend('custom', function ($app) {
    return new CustomCompressionDriver();
});
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Your Name](https://github.com/yourgithubhandle)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.