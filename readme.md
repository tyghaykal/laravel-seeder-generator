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
| 10.x          | ^1.4       |
| 9.x           | ^1.4       |
| 8.x           | ^1.4       |
| 7.x           | ^1.4       |
| 6.x           | ^1.4       |
| 5.8.x         | ^1.4       |
| 5.7.x & below | Not tested |

## Install

```bash
composer require --dev tyghaykal/laravel-seed-generator
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
| --show-prompt                     | Show menu in prompt                                                         |
| --all-ids                         | Seed All ids                                                                |
| --all-fields                      | Seed All fields                                                             |
| --without-relations               | Seed All data without relations                                             |
| --where=field,value               | Select data with where clause                                               |
| --where-in=field,value1,value2    | Select data with where in clause                                            |
| --limit=int                       | limit seeded data, value must be integer                                    |
| --ids="id1,id2"                   | The ids to be seeded, cannot be used simultaneously with --ignore-ids       |
| --ignore-ids="id1,id2"            | The ids to be ignored, cannot be used simultaneously with --ids             |
| --fields="field1,field2"          | The fields to be seeded, cannot be used simultaneously with --ignore-fields |
| --ignore-fields="field1,field2"   | The fields to be ignored, cannot be used simultaneously with --fields       |
| --relations="relation1,relation2" | The relation to be seeded, must be function name of has many relation       |
| --relations-limit=int             | Limit relation data to be seeded, value must be integer                     |
| --output                          | The location of seeder file                                                 |

### Usage with Option

Change the `ModelNamespace` into your model Namespace without \App\Models or \App, for example your model namespace is under `\App\Models\Master\Type`, so you just need type `Master\Type`

```bash
php artisan seed:generate Master\Type
```

You can run the command and show the menu using

```bash
php artisan seed:generate Master\Type --show-prompt
```

You can filter which data will be included into seeder file using **where** clause

```bash
php artisan seed:generate Master\Type --where=field,value
```

You can filter which data will be included into seeder file using **where in** clause

```bash
php artisan seed:generate Master\Type --where-in=field,value1,value2
```

You can limit the data will be included into seeder file using **where in** clause

```bash
php artisan seed:generate Master\Type --limit=10
```

You can also define which data you want to include to seeder file based on the id with:

```bash
php artisan seed:generate ModelNamespace --ids="1,2,3"
```

Or you want to skip some ids:

```bash
php artisan seed:generate ModelNamespace --ignore-ids="1,2,3"
```

You can also define which field that you want include to seeder file based on the field name with:

```bash
php artisan seed:generate ModelNamespace --fields="id,name"
```

Or you want skip some fields:

```bash
php artisan seed:generate ModelNamespace --ignore-fields="id,name"
```

You can also define which hasMany relation that you want seed:

```bash
php artisan seed:generate ModelNamespace --relations="relationName1,relationName2"
```

You can also limit the relation that you want seed:

```bash
php artisan seed:generate ModelNamespace --relations="relationName1,relationName2" --relations-limit=10
```

You can also change the location of generated seeder file:

```bash
php artisan seed:generate ModelNamespace --output=Should/Be/In/Here/Data

// it will produce in path database/seeders/Should/Be/In/Here/DataSeeder
// or
// it will produce in path database/seeds/Should/Be/In/Here/DataSeeder
```
