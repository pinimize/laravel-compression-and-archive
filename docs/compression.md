# Compression

- [Introduction](#introduction)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
    - [Compressing Strings](#compressing-strings)
    - [Compressing Files](#compressing-files)
    - [Compressing Resources](#compressing-resources)
    - [Writing Compressed Content](#writing-compressed-content)
- [Advanced Usage](#advanced-usage)
    - [Downloading Compressed Files](#downloading-compressed-files)
    - [Compression Ratio](#compression-ratio)
    - [Supported Algorithms](#supported-algorithms)
- [Using the `put` Method](#using-the-put-method)
    - [Supported Data Types](#supported-data-types)
    - [Using Storage Disks](#using-storage-disks)
- [Drivers](#drivers)
    - [Gzip Driver](#gzip-driver)
    - [Zlib Driver](#zlib-driver)
- [Custom Drivers](#custom-drivers)

<a name="introduction"></a>
## Introduction

Laravel Compression provides a powerful abstraction for file compression, allowing you to easily compress and decompress data using various algorithms. It offers a consistent API across different compression methods, making it simple to switch between them as needed.

<a name="configuration"></a>
## Configuration

The compression configuration file is typically located at `config/compression.php`. In this file, you can specify the default driver and any driver-specific options:

```php
return [
    'default' => 'gzip',

    'drivers' => [
        'gzip' => [
            'level' => -1,
        ],
        'zlib' => [
            'level' => -1,
            'encoding' => ZLIB_ENCODING_DEFLATE,
        ],
    ],
];
```

<a name="basic-usage"></a>
## Basic Usage

<a name="compressing-strings"></a>
### Compressing Strings

To compress data to a string, you can use the `string` method.

```php
use Illuminate\Support\Facades\Compression;

$compressed = Compression::string('Hello, World!');
```

<a name="compressing-files"></a>
### Compressing Files

To compress a file, use the `file` method:

```php
Compression::file('path/to/input.txt', 'path/to/output.gz');
```

<a name="compressing-resources"></a>
### Compressing Resources

You can compress PHP resources using the `resource` method:

```php
$resource = fopen('path/to/input.txt', 'r');
$compressedResource = Compression::resource($resource);
```

<a name="writing-compressed-content"></a>
### Writing Compressed Content

To write compressed content to a file, use the `put` method:

```php
Compression::put('path/to/output.gz', 'Content to be compressed');
```

<a name="advanced-usage"></a>
## Advanced Usage

<a name="downloading-compressed-files"></a>
### Downloading Compressed Files

To create a download response for a compressed file:

```php
return Compression::download('path/to/file.txt', 'downloaded_file.gz');
```

<a name="compression-ratio"></a>
### Compression Ratio

To get the compression ratio between original and compressed data:

```php
$ratio = Compression::getRatio('original_content', 'compressed_content');
```

<a name="supported-algorithms"></a>
### Supported Algorithms

To get a list of supported compression algorithms for the current driver:

```php
$algorithms = Compression::getSupportedAlgorithms();
```

<a name="using-the-put-method"></a>
## Using the `put` Method

The `put` method is a versatile way to write compressed content to a file. It supports various input types and offers flexibility in where the compressed file is stored.

<a name="supported-data-types"></a>
### Supported Data Types

The `put` method can handle several types of input:

1. **Strings**:
   ```php
   Compression::put('output.gz', 'String content to compress');
   ```

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

<a name="using-storage-disks"></a>
### Using Storage Disks

By default, the `put` method writes files to the local filesystem. However, you can use Laravel's Storage facade to write compressed files to any configured disk by specifying the `disk` option:

```php
use Illuminate\Support\Facades\Compression;

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

<a name="drivers"></a>
## Drivers

<a name="gzip-driver"></a>
### Gzip Driver

The Gzip driver uses the `gzencode` function for compression. It supports the `FORCE_GZIP` encoding and produces files with a `.gz` extension.

<a name="zlib-driver"></a>
### Zlib Driver

The Zlib driver uses the `zlib_encode` function for compression. It supports `ZLIB_ENCODING_RAW`, `ZLIB_ENCODING_GZIP`, and `ZLIB_ENCODING_DEFLATE` encodings. Files compressed with this driver have a `.zz` extension.

<a name="custom-drivers"></a>
## Custom Drivers

You can create custom compression drivers by extending the `AbstractCompressionDriver` class and implementing the required methods. Then, register your custom driver in a service provider:

```php
use Illuminate\Support\Facades\Compression;

public function boot()
{
    Compression::extend('custom', function ($app) {
        return new CustomCompressionDriver($app['config']['compression.custom']);
    });
}
```

After registering your custom driver, you can use it like any other compression driver in your application.