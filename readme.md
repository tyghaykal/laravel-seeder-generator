# Laravel Seeder Generator

![Test CI](https://github.com/tyghaykal/laravel-seeder-generator/actions/workflows/test.yml/badge.svg?branch=main)
[![Latest Stable Version](http://poser.pugx.org/tyghaykal/laravel-seed-generator/v)](https://packagist.org/packages/tyghaykal/laravel-seed-generator) [![Total Downloads](http://poser.pugx.org/tyghaykal/laravel-seed-generator/downloads)](https://packagist.org/packages/tyghaykal/laravel-seed-generator) [![Latest Unstable Version](http://poser.pugx.org/tyghaykal/laravel-seed-generator/v/unstable)](https://packagist.org/packages/tyghaykal/laravel-seed-generator) [![License](http://poser.pugx.org/tyghaykal/laravel-seed-generator/license)](https://packagist.org/packages/tyghaykal/laravel-seed-generator)

Generate laravel seeder file from a real data from your database.

## Supported Database

-   [x] MariaDB
-   [x] MySQL
-   [x] PostgreSQL
-   [x] SQL Server
-   [x] SQLite

## Version Compatibility

| Laravel       | Version    |
| ------------- | ---------- |
| 10.x          | ^1.0       |
| 9.x           | ^1.0       |
| 8.x           | ^1.0       |
| 7.x           | ^1.0       |
| 6.x           | ^1.0       |
| 5.8.x         | ^1.0       |
| 5.7.x & below | Not tested |

## Install

```bash
composer require --dev tyghaykal/laravel-seeder-generator
```

## Laravel Setup

Laravel will automatically register the service provider for you, so no more step on this.

## Usage

To generate your seeder file, you can run:

```bash
php artisan seed:generate ModelNamespace --no-additional
```

Or you just can type like below then it will prompt you to fill some specification:

```bash
php artisan seed:generate
```

### Options

| Option                            | Description                                                                 |
| --------------------------------- | --------------------------------------------------------------------------- |
| --no-additional                   | Seed All data and ignore all options                                        |
| --all-ids                         | Seed All ids                                                                |
| --all-fields                      | Seed All fields                                                             |
| --without-relations               | Seed All data without relations                                             |
| --ids="id1,id2"                   | The ids to be seeded, cannot be used simultaneously with --ignore-ids       |
| --ignore-ids="id1,id2"            | The ids to be ignored, cannot be used simultaneously with --ids             |
| --fields="field1,field2"          | The fields to be seeded, cannot be used simultaneously with --ignore-fields |
| --ignore-fields="field1,field2"   | The fields to be ignored, cannot be used simultaneously with --fields       |
| --relations="relation1,relation2" | The relation to be seeded, must be function name of has many relation       |

### Usage with Option

Change the `ModelNamespace` into your model Namespace without \App\Models or \App, for example your model namespace is under `\App\Models\Master\Type`, so you just need type `Master\Type`

```bash
php artisan seed:generate Master\Type
```

You can also define which data you want to include to seeder file based on the id with:

```bash
php artisan seed:generate ModelNamespace --ids="1,2,3" --all-fields --without-relations
```

Or you want to skip some ids:

```bash
php artisan seed:generate ModelNamespace --ignore-ids="1,2,3" --all-fields --without-relations
```

You can also define which field that you want include to seeder file based on the field name with:

```bash
php artisan seed:generate ModelNamespace --fields="id,name" --all-ids --without-relations
```

Or you want skip some fields:

```bash
php artisan seed:generate ModelNamespace --ignore-fields="id,name" --all-ids --without-relations
```

You can also define which hasMany relation that you want seed:

```bash
php artisan seed:generate ModelNamespace --all-ids --all-fields --relations="relationName1,relationName2"
```
