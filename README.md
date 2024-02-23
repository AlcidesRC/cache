[![Integration Tests](https://github.com/fonil/cache/actions/workflows/integration-tests.yml/badge.svg)](https://github.com/fonil/cache/actions/workflows/integration-tests.yml)

# Cache Packer

This repository contains a PHP class that allows to optimize the cacheable arrays in order to optimize the required memory consumption.

[TOC]

## Installation

You can install the package via composer:

```bash
$ composer require fonil/cache
```

## How It Works

In many scenarios we are caching structured information (generally coming from database). Each row from this structure has the same schema than the previous row, and the same schema than the next one.

When serializing this kind of structures prior to cache the column names are stored again and again as many times as rows are contained, consuming resources to track an information is not mutable and predictable at all. 

The Packer class brings a static method called `pack` which allows to create a new structure with the following schema:

```php
$packed = [
  'keys' => ['column-name-1', 'column-name-2', ..., 'column-name-z'],
  'data' => [
  	[
        'row-1-value-1', 
        'row-1-value-2', 
        ... 
        'rown-1-value-z'
    ],
    [
        'row-n-value-1', 
        'row-n-value-2', 
        ... 
        'rown-n-value-z'
    ],
  ],
];
```

> This optimized schema can now be cached and reduce a 25% of memory consumptions in average for large datasets. See Statistics section for further details.

To revert this schema to the original structure the Packer class also brings a static method called `unpack` which restores the schema to initial stage.

### Example

#### Source Dataset

```php
// $users = DB::table('users')->get()
$users = [
  [
    'id' => 1,
    'firstName' => 'Hope',
    'lastName' => 'Pacocha',
    'email' => 'eblanda@hotmail.com',
    'address' => '1939 Julio Shore. Zboncakland, HI 21531-7243',
    'city' => 'Lethatown',
    'postcode' => '84445-4109',
    'country' => 'Niger',
  ],
  [
    'id' => 2,
    'firstName' => 'Lyric',
    'lastName' => 'Parker',
    'email' => 'bennett.mitchell@balistreri.org',
    'address' => '25071 Jacklyn Dam Suite 215. Lake Alexannemouth, IL 77929-0777',
    'city' => 'Port Yvetteville',
    'postcode' => '00537',
    'country' => 'French Southern Territories',
  ],
];
```

#### Direct Serialization

```php
a:2:{i:0;a:8:{s:2:"id";i:1;s:9:"firstName";s:4:"Hope";s:8:"lastName";s:7:"Pacocha";s:5:"email";s:19:"eblanda@hotmail.com";s:7:"address";s:43:"1939 Julio Shore
Zboncakland, HI 21531-7243";s:4:"city";s:9:"Lethatown";s:8:"postcode";s:10:"84445-4109";s:7:"country";s:5:"Niger";}i:1;a:8:{s:2:"id";i:2;s:9:"firstName";s:5:"Lyric";s:8:"lastName";s:6:"Parker";s:5:"email";s:31:"bennett.mitchell@balistreri.org";s:7:"address";s:61:"25071 Jacklyn Dam Suite 215
Lake Alexannemouth, IL 77929-0777";s:4:"city";s:16:"Port Yvetteville";s:8:"postcode";s:5:"00537";s:7:"country";s:27:"French Southern Territories";}}
```

#### Serialization with Packer

```php
a:2:{s:4:"keys";a:8:{i:0;s:2:"id";i:1;s:9:"firstName";i:2;s:8:"lastName";i:3;s:5:"email";i:4;s:7:"address";i:5;s:4:"city";i:6;s:8:"postcode";i:7;s:7:"country";}s:4:"data";a:2:{i:0;a:8:{i:0;i:1;i:1;s:4:"Hope";i:2;s:7:"Pacocha";i:3;s:19:"eblanda@hotmail.com";i:4;s:43:"1939 Julio Shore
Zboncakland, HI 21531-7243";i:5;s:9:"Lethatown";i:6;s:10:"84445-4109";i:7;s:5:"Niger";}i:1;a:8:{i:0;i:2;i:1;s:5:"Lyric";i:2;s:6:"Parker";i:3;s:31:"bennett.mitchell@balistreri.org";i:4;s:61:"25071 Jacklyn Dam Suite 215
Lake Alexannemouth, IL 77929-0777";i:5;s:16:"Port Yvetteville";i:6;s:5:"00537";i:7;s:27:"French Southern Territories";}}}
```

##### Unserialization

```php
[
  'keys' => ['id', 'firstName', 'lastName', 'email', 'address', 'city', 'postcode', 'country'],
  'data' => [
    [
      1,
      'Hope',
      'Pacocha',
      'eblanda@hotmail.com',
      '1939 Julio Shore. Zboncakland, HI 21531-7243',
      'Lethatown',
      '84445-4109',
      'Niger',
    ],
    [ 
      2,
      'Lyric',
      'Parker',
      'bennett.mitchell@balistreri.org',
      '25071 Jacklyn Dam Suite 215. Lake Alexannemouth, IL 77929-0777',
      'Port Yvetteville',
      '00537',
      'French Southern Territories',
    ],
  ],
];
```

### Statistics

| Rows  | Direct Serialized | Serialized with Packer | Optimization Percentage |
| ----- | ----------------- | ---------------------- | ----------------------- |
| 9     | 2625 bytes        | 2131 bytes             | 18.82 %                 |
| 99    | 28440 bytes       | 21286 bytes            | 25.15 %                 |
| 999   | 289144 bytes      | 215390 bytes           | 25.51 %                 |
| 9999  | 2914783 bytes     | 2175029 bytes          | 25.38 %                 |
| 99999 | 29340860 bytes    | 21941106 bytes         | 25.22 %                 |

## Usage

This package can be used as a library.

### Example: using the library

```php
<?php

use Fonil\Cache\Packer;

// Reduce by 25% memory consuptions in average by using Packer
$users = Packer::unpack(cache()->remember('users', 300, function () {
    return Packer::pack(
        DB::table('users')->get()
    );
}));
```

## Testing

You can run the test suite via composer:

```
$ composer tests
```

### Unit Tests

This library provides a [PHPUnit](https://phpunit.de/) testsuite with **12 unit tests** and **22 assertions**:

```bash
Time: 00:01.038, Memory: 20.00 MB

OK (12 tests, 22 assertions)
```

### Code Coverage

Code Coverage report summary:

```
Code Coverage Report:     
  2024-02-23 10:06:34     
                          
 Summary:                 
  Classes: 100.00% (1/1)  
  Methods: 100.00% (2/2)  
  Lines:   100.00% (23/23)

Fonil\Cache\Exceptions\WrongPackerSchemaException
  Methods:  ( 0/ 0)   Lines:  (  0/  0)
Fonil\Cache\Packer
  Methods: 100.00% ( 2/ 2)   Lines: 100.00% ( 23/ 23)
```

## QA

### Static Analyzer

You can check this library with [PHPStan](https://phpstan.org/):

```
$ composer analyse
```

This command generates the following report:

```bash
> phpstan analyse --configuration=phpstan.neon --memory-limit 1G --ansi
 3/3 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

[OK] No errors
```

### Checking the Coding Style

You can check this library with [PHP_CodeSniffer ](https://github.com/squizlabs/PHP_CodeSniffer):

```bash
$ composer check-style
```

This command generates the following report:

```bash
> phpcs -p --standard=PSR12 --exclude=Generic.Files.LineLength --runtime-set ignore_errors_on_exit 1 --runtime-set ignore_warnings_on_exit 1 src tests
... 3 / 3 (100%)

Time: 49ms; Memory: 8MB
```

## Security Vulnerabilities

Please review our security policy on how to report security vulnerabilities:

> **PLEASE DON'T DISCLOSE SECURITY-RELATED ISSUES PUBLICLY**

### Supported Versions

Only the latest major version receives security fixes.

### Reporting a Vulnerability

If you discover a security vulnerability within this project, please [open an issue here](https://github.com/fonil/cache/issues). All security vulnerabilities will be promptly addressed.

## License

The MIT License (MIT). Please see [License File](https://github.com/fonil/cache/blob/main/LICENSE) for more information.
