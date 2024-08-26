# Pinimize

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pinimize/laravel-compression-and-archive.svg?style=flat-square)](https://packagist.org/packages/pinimize/laravel-compression-and-archive)
[![Total Downloads](https://img.shields.io/packagist/dt/pinimize/laravel-compression-and-archive.svg?style=flat-square)](https://packagist.org/packages/pinimize/laravel-compression-and-archive)
[![Tests](https://github.com/pinimize/laravel-compression-and-archive/actions/workflows/phpunit.yml/badge.svg?branch=main)](https://github.com/pinimize/laravel-compression-and-archive/actions/workflows/phpunit.yml)
[![License](https://img.shields.io/packagist/l/pinimize/laravel-compression-and-archive.svg?style=flat-square)](https://packagist.org/packages/pinimize/laravel-compression-and-archive)

Pinimize is a powerful Laravel package that simplifies file compression and decompression. It provides a clean and intuitive API for handling various compression and decompression tasks in your Laravel applications, with full support for Laravel's Storage system.

Archiving and unarchiving operations are in **coming very soon**.

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

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
    - [Default Driver](#default-driver)
    - [Compression Drivers](#compression-drivers)
    - [Compression Levels](#compression-levels)
    - [Storage Disk](#storage-disk)
- [Environment Variables](#environment-variables)
- [Basic Usage](#basic-usage)
    - [String Macros](#string-macros)
    - [Storage Macros](#storage-macros)
    - [Compressing Strings](#compressing-strings)
    - [Compressing Resources](#compressing-resources)
    - [Compressing Files](#compressing-files)
    - [Using Storage Disks](#using-storage-disks)
- [Supported Data Types](#supported-data-types)
- [Advanced Usage](#advanced-usage)
    - [Downloading Compressed Files](#downloading-compressed-files)
    - [Compression Ratio](#compression-ratio)
    - [Supported Algorithms](#supported-algorithms)
- [Drivers](#drivers)
    - [Gzip Driver](#gzip-driver)
    - [Zlib Driver](#zlib-driver)
- [Custom Drivers](#custom-drivers)
- [Testing](#testing)
- [Changelog](#changelog)
- [Credits](#credits)
- [License](#license)

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

The `config/pinimize.php` file allows you to configure the compression and decompression settings for the Pinimize package. This file is created when you publish the package configuration.

### Default Driver

You can set the default compression driver using the `COMPRESSION_DRIVER` environment variable or by directly modifying the `compression.default` value in the config file. The default is set to 'gzip'.

```php
'default' => env('COMPRESSION_DRIVER', 'gzip'),
```

### Compression Drivers

The package supports two compression drivers: 'gzip' and 'zlib'. Each driver has its own configuration options:

#### Gzip Driver

```php
'gzip' => [
    'level' => env('GZIP_LEVEL', -1),
    'encoding' => FORCE_GZIP,
    'disk' => env('COMPRESSION_DISK', null),
],
```

#### Zlib Driver

```php
'zlib' => [
    'level' => env('ZLIB_LEVEL', -1),
    'encoding' => ZLIB_ENCODING_DEFLATE,
    'disk' => env('COMPRESSION_DISK', null),
],
```

More drivers will be added in future releases. They are kept separate to avoid requiring you to install php extensions you might not need and to keep the codebase clean.

### Compression Levels

For both drivers, you can set the compression level:

- `-1`: default compression (recommended for most cases)
- `0`: no compression
- `1`: fastest compression
- `9`: best compression

### Storage Disk

The `disk` option allows you to specify which disk to use for file operations. This integrates with Laravel's Storage system:

- If set to `null` (default), the local filesystem will be used.
- You can set it to any configured disk in your `config/filesystems.php` file.

To set the disk, use the `COMPRESSION_DISK` environment variable or modify the `disk` value directly in the config file.

## Environment Variables

For easy configuration, you can use the following environment variables:

- `COMPRESSION_DRIVER`: Set the default compression driver ('gzip' or 'zlib')
- `GZIP_LEVEL`: Set the compression level for the gzip driver
- `ZLIB_LEVEL`: Set the compression level for the zlib driver
- `COMPRESSION_DISK`: Set the storage disk for file operations

Remember to update your `.env` file with these variables as needed.

## Basic Usage

### String Macros

The Pinimize package extends Laravel's `Str` facade with two convenient macros for string compression and decompression:

```php
Str::compress($data)

// and

Str::decompress($compressedData)
```

These macros allow you to easily compress & decompress data using the default compression driver.

```php
use Illuminate\Support\Str;

// Using the default driver
$originalString = "This is a long string that will be compressed.";
$compressedString = Str::compress($originalString);

// Specifying a driver
$compressedStringGzip = Str::compress($originalString, 'gzip');
$compressedStringZlib = Str::compress($originalString, 'zlib');
```

To decompress compressed data:

```php
use Illuminate\Support\Str;

$decompressedString = Str::decompress($compressedString);

// Specifying a driver
$decompressedStringGzip = Str::decompress($compressedStringGzip, 'gzip');
$decompressedStringZlib = Str::decompress($compressedStringZlib, 'zlib');
```

These macros provide a simple and convenient way to compress and decompress strings in your Laravel application, leveraging the power of the Pinimize package.

### Storage Macros

The Pinimize package extends Laravel's `Storage` facade with two convenient methods for file compression and decompression:

#### Compression:

```php
use Illuminate\Support\Facades\Storage;

Storage::compress(
    string $source,
    ?string $destination = null,
    bool $deleteSource = false,
    ?string $driver = null
): bool|string;
```

#### Parameters:

- `$source`: The path to the source file (relative to the storage disk).
- `$destination`: (Optional) The path where the compressed file should be saved. If null, it will use the source filepath with the extension appended.
- `$deleteSource`: (Optional) Whether to delete the source file after successful compression. Defaults to `false`.
- `$driver`: (Optional) The compression driver to use (e.g., 'gzip', 'zlib'). If null, it will use the default driver.

#### Return Value:

- If `$destination` is provided: Returns `true` on success, `false` on failure.
- If `$destination` is null: Returns the path of the compressed file on success, `false` on failure.

#### Decompression:

```php
use Illuminate\Support\Facades\Storage;

Storage::decompress(
    string $source,
    ?string $destination = null,
    bool $deleteSource = false,
    ?string $driver = null
): string|bool;
```

#### Parameters:

- `$source`: The path to the compressed file (relative to the storage disk).
- `$destination`: (Optional) The path where the decompressed file should be saved. If null, it will use `$source` with the compression extension removed.
- `$deleteSource`: (Optional) Whether to delete the source file after successful decompression. Defaults to `false`.
- `$driver`: (Optional) The decompression driver to use (e.g., 'gzip', 'zlib'). If null, it will use the default driver.

#### Return Value:

- If `$destination` is provided: Returns `true` on success, `false` on failure.
- If `$destination` is null: Returns the path of the decompressed file on success, `false` on failure.

### Examples:

```php
use Illuminate\Support\Facades\Storage;

// Compress a file with default settings
$compressResult = Storage::compress('large_file.txt');
if ($compressResult !== false) {
    echo "File compressed successfully to: $compressResult\n";
}

// Compress a file with custom destination and driver
$compressResult = Storage::compress('document.pdf', 'compressed_doc.pdf.gz', false, 'zlib');
if ($compressResult === true) {
    echo "File compressed successfully\n";
}

// Decompress a file with default settings
$decompressResult = Storage::decompress('large_file.txt.gz');
if ($decompressResult !== false) {
    echo "File decompressed successfully to: $decompressResult\n";
}

// Decompress a file, delete the source, and use a specific driver
$decompressResult = Storage::decompress('archive.tar.gz', 'extracted_archive.tar', true, 'gzip');
if ($decompressResult === true) {
    echo "File decompressed and compressed version deleted\n";
}
```

These methods provide a simple and convenient way to compress and decompress files in your Laravel application's storage system, leveraging the power of the Pinimize package.

### Compressing Strings

To compress data to a string, you can use the `string` method. Take a look at the [Supported Data Types](#supported-data-types) for this method. This method is useful for compressing small amounts of data in memory.

```php
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Compression;

// With string input
$compressed = Compression::string('Hello, World!');

// With file input
$compressed = Compression::string(new File($path));

// With uploaded input
$compressed = Compression::string(new UploadedFile($path));
// etc.
```

The Decompression facade works in a similar way, only it decompresses the data:

```php
use Illuminate\Support\Facades\Decompression;

$data = gzencode('Hello, World!');
$compressed = Decompression::string($data); // Hello, World!
```

### Compressing Resources

You can compress data and have the compressed content returned as a resource by using the `resource` method. This method useful for working with data without loading everything into memory.

Take a look at the [Supported Data Types](#supported-data-types) for this method.

```php
use Illuminate\Support\Facades\Compression;

// string input
$compressedResource = Compression::resource("I'm too big, make me smaller please!");

// resource input
$resource = fopen('path/to/input.txt', 'r');
$compressedResource = Compression::resource($resource);
```

The Decompression facade works in a similar way, only it decompresses the resource:

```php
use Illuminate\Support\Facades\Decompression;

// resource input
$resource = fopen('path/to/compressed_data.txt.gz', 'r');

$decompressedResource = Decompression::resource($resource);
```

### Compressing Files

The `put` method is a versatile way to write compressed content to a file. It supports various input types and offers flexibility in where the compressed file is stored.

```php
use Illuminate\Http\File;

// Storing on a local disk
Compression::put('local_output.gz', 'Content');

// Compressing a file and storing on a local disk
Compression::put('ftp_output.gz', new File($path));

// Storing on Google Cloud Storage
Compression::put('gcs_output.gz', 'Content');
```

The Decompression facade works in a similar way, only it decompresses the resource:

```php
use Illuminate\Support\Facades\Decompression;

// resource input
$resource = fopen('path/to/compressed.txt.gz', 'r');

$decompressedResource = Decompression::put('local_output.txt', $resource);
$decompressedResource = Decompression::put('local_output.txt', 'path/to/compressed.txt.gz');
```

### Using Storage Disks

By default, the `put` method will use the filesystem provided in then config file, which defaults to the local filesystem. However, you can use Laravel's Storage facade to write compressed files to any configured disk by specifying the `disk` option:

```php
use Illuminate\Support\Facades\Compression;

// Storing on an S3 bucket, the path is relative to the bucket's root
Compression::put('compressed/output.gz', 'Content to compress', [
    'disk' => 's3'
]);
```

This will compress the content and store it on the S3 disk (assuming you have configured an S3 disk in your `filesystems.php` configuration).

You can use any disk configured in your `config/filesystems.php`:

```php
// Storing on a local disk
Compression::put('local_output.gz', 'Content', ['disk' => 'local']);

// Storing on an FTP server
Compression::put('ftp_output.gz', 'Content', ['disk' => 'ftp']);

// Storing on Google Cloud Storage
Compression::put('gcs_output.gz', 'Content', ['disk' => 'gcs']);
```

When using the `disk` option, the Compression service will utilize Laravel's Storage facade to handle file operations, allowing you to take advantage of all the benefits of Laravel's filesystem abstraction.

Note that when using storage disks, the path you provide as the first argument to `put` will be relative to the disk's configured root directory.

### Supported Data Types

The `string`, `resource` and `put` methods can handle several types of input:

1. **Strings**:
   ```php
   Compression::put('output.gz', 'String content to compress');
   ```

If the provided string is a path to a file, the package will first attempt to locate the file on the specified filesystem.
If the file cannot be found, the package will treat the string as raw content to be compressed.

This behavior allows flexibility in handling both file paths and direct content compression within the same interface.
If you want to be certain that a file specified by a path should be loaded, consider using the `Illuminate\Http\File` object or passing it in as a resource.

2. **PSR-7 StreamInterface**:
   ```php
   use GuzzleHttp\Psr7\Stream;
   
   $stream = new Stream(fopen('path/to/file.txt', 'r'));
   Compression::put('output.gz', $stream);
   ```

3. **Laravel's File object**:
   ```php
   use Illuminate\Http\File;
   
   $file = new File('path/to/file.txt');
   Compression::put('output.gz', $file);
   ```

4. **Laravel's UploadedFile object**:
   ```php
   // In a controller method handling file upload
   public function handleUpload(Request $request)
   {
       $uploadedFile = $request->file('document');
       Compression::put('compressed_upload.gz', $uploadedFile);
   }
   ```

5. **PHP resource**:
   ```php
   $resource = fopen('path/to/file.txt', 'r');
   Compression::put('output.gz', $resource);
   ```

## Advanced Usage

### Downloading Compressed Files

To create a download response for a compressed file:

```php
return Compression::download('path/to/file.txt', 'downloaded_file.gz');
```

This also works for decompressing files:

```php
return Decompression::download('path/to/file.txt.gz', 'file.txt');
```

### Compression Ratio

To get the compression ratio between original and compressed data:

```php
$ratio = Compression::getRatio('original_content', 'compressed_content');
```

### Supported Algorithms

To get a list of supported compression algorithms for the current driver:

```php
$algorithms = Compression::getSupportedAlgorithms();
```

## Drivers

### Gzip Driver

The Gzip driver uses the `gzencode` function for compression. It supports the `FORCE_GZIP` encoding and produces files with a `.gz` extension.

### Zlib Driver

The Zlib driver uses the `zlib_encode` function for compression. It supports `ZLIB_ENCODING_RAW`, `ZLIB_ENCODING_GZIP`, and `ZLIB_ENCODING_DEFLATE` encodings. Files compressed with this driver have a `.zz` extension.

## Custom Drivers

You can create custom compression drivers by extending the `CompressionContract` interface and implementing the required methods. Then, register your custom driver in a service provider:

```php
use Illuminate\Support\Facades\Compression;

public function boot()
{
    Compression::extend('custom', function ($app) {
        return new CustomCompressionDriver($app['config']['compression.custom']);
    });
}
```

After registering your custom driver, you can use it like any other compression driver in your application. The same process applies for creating custom decompression drivers.

## Testing
Run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Einar Hansen](https://github.com/einar-hansen)
- [All Contributors](https://github.com/pinimize/laravel-compression-and-archive/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.