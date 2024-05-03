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

| Laravel        | Version    |
| -------------- | ---------- |
| 11.x           | ^2.0.\*    |
| 10.x           | ^2.0.\*    |
| 9.x            | ^2.0.\*    |
| 8.x            | ^2.0.\*    |
| 7.x            | ^2.0.\*    |
| 6.x            | ^2.0.\*    |
| 5.8.x          | ^2.0.\*    |
| 5.7.x & before | Not tested |

## Install

```bash
composer require --dev tyghaykal/laravel-seed-generator
```

## Laravel Setup

Laravel will automatically register the service provider for you, so no more step on this.

You can generate a config file to personalize this package using `vendor:publish` and choose `TYGHaykal\LaravelSeedGenerator\SeedGeneratorServiceProvider`.

```bash
php artisan vendor:publish
```

In the config file, you can adjust some of settings on generating seeders file such as namespace, prefix, suffix, and connection.

## Usage

### Options

| Option                             | Description                                                                               |
| ---------------------------------- | ----------------------------------------------------------------------------------------- |
| --show-prompt                      | Show menu in prompt                                                                       |
| --mode=                            | Set the source mode (model or table)                                                      |
| --table-mode                       | Set the source mode into table                                                            |
| --model-mode                       | Set the source mode into model                                                            |
| --models=                          | Generate seed for selected model, can be multiple separated with comma                    |
| --tables=                          | Generate seed for selected table, can be multiple separated with comma                    |
| --all-tables                       | Generate seed for all tables                                                              |
| --where-raw-query=rawQuery         | Select data with Raw Query clause                                                         |
| --where=field,type,value           | Select data with where clause                                                             |
| --where-in=field,value1,value2     | Select data with where in clause                                                          |
| --where-not-in=field,value1,value2 | Select data with where not in clause                                                      |
| --order-by=field,type(asc,desc)    | Order data to be seeded                                                                   |
| --limit=int                        | limit seeded data, value must be integer                                                  |
| --all-ids                          | Seed All ids                                                                              |
| --ids="id1,id2"                    | The ids to be seeded, cannot be used simultaneously with --ignore-ids                     |
| --ignore-ids="id1,id2"             | The ids to be ignored, cannot be used simultaneously with --ids                           |
| --all-fields                       | Seed All fields                                                                           |
| --fields="field1,field2"           | The fields to be seeded, cannot be used simultaneously with --ignore-fields               |
| --ignore-fields="field1,field2"    | The fields to be ignored, cannot be used simultaneously with --fields                     |
| --without-relations                | Seed All data without relations, only on model mode                                       |
| --relations="relation1,relation2"  | The relation to be seeded, must be function name of has many relation, only on model mode |
| --relations-limit=int              | Limit relation data to be seeded, value must be integer, only on model mode               |
| --output                           | The location of seeder file                                                               |
| --no-seed                          | Skip include the database seeder file                                                     |

### Usage on Model with Option

Just set the model value with your model Namespace without `\App\Models` or `\App` or your defined namespace on `config.php`, for example your model namespace is under `\App\Models\Master\Type`, so you just need type `Master\Type`. Can be multiple and separated with comma.

```bash
php artisan seed:generate --model-mode --models=Master\Type
```

You can run the command and show the menu using

```bash
php artisan seed:generate --model-mode --models=Master\Type --show-prompt
```

You can filter which data will be included into seeder file using **where** clause

```bash
php artisan seed:generate --model-mode --models=Master\Type --where=field,type,value
```

You can filter which data will be included into seeder file using **where in** clause

```bash
php artisan seed:generate --model-mode --models=Master\Type --where-in=field,value1,value2
```

You can filter which data will not be included into seeder file using **where not in** clause

```bash
php artisan seed:generate --model-mode --models=Master\Type --where-in=field,value1,value2
```

You can order the data will be included into seeder file using **order-by** clause

```bash
php artisan seed:generate --model-mode --models=Master\Type --order-by=field,type
```

You can limit the data will be included into seeder file using **limit** clause

```bash
php artisan seed:generate --model-mode --models=Master\Type --limit=10
```

You can also define which data you want to include to seeder file based on the id with, can only run with single model only:

```bash
php artisan seed:generate --model-mode --models=Master\Type --ids="1,2,3"
```

Or you want to skip some ids:

```bash
php artisan seed:generate --model-mode --models=Master\Type --ignore-ids="1,2,3"
```

You can also define which field that you want include to seeder file based on the field name with:

```bash
php artisan seed:generate --model-mode --models=Master\Type --fields="id,name"
```

Or you want skip some fields:

```bash
php artisan seed:generate --model-mode --models=Master\Type --ignore-fields="id,name"
```

You can also define which hasMany relation that you want seed, only has effect on model mode with single model only:

```bash
php artisan seed:generate --model-mode --models=Master\Type --relations="relationName1,relationName2"
```

You can also limit the relation that you want seed, only has effect on model mode with single model only:

```bash
php artisan seed:generate --model-mode --models=Master\Type --relations="relationName1,relationName2" --relations-limit=10
```

You can also change the location of generated seeder file:

```bash
php artisan seed:generate --model-mode --models=Master\Type --output=Should/Be/In/Here/Data

// it will produce in path database/seeders/Should/Be/In/Here/DataSeeder
// or
// it will produce in path database/seeds/Should/Be/In/Here/DataSeeder
```

By default, every generated seeders file will be presented on DatabaseSeeder.php, if you don't want it, you can use **--no-seed** option:

```bash
php artisan seed:generate --model-mode --models=Master\Type --no-seed
```

### Usage on Table with Option

Just set the tables value as table name, can be multiple separated with comma

```bash
php artisan seed:generate --table-mode --tables=master_leave_types
```

You can run the command and show the menu using

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --show-prompt
```

You can filter which data will be included into seeder file using **where** clause

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --where=field,type,value
```

You can filter which data will be included into seeder file using **where in** clause

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --where-in=field,value1,value2
```

You can filter which data will not be included into seeder file using **where not in** clause

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --where-in=field,value1,value2
```

You can order the data will be included into seeder file using **order-by** clause

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --order-by=field,type
```

You can limit the data will be included into seeder file using **limit** clause

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --limit=10
```

You can also define which data you want to include to seeder file based on the id with:

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --ids="1,2,3"
```

Or you want to skip some ids:

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --ignore-ids="1,2,3"
```

You can also define which field that you want include to seeder file based on the field name with:

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --fields="id,name"
```

Or you want skip some fields:

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --ignore-fields="id,name"
```

You can also change the location of generated seeder file:

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --output=Should/Be/In/Here/Data

// it will produce in path database/seeders/Should/Be/In/Here/DataSeeder
// or
// it will produce in path database/seeds/Should/Be/In/Here/DataSeeder
```

By default, every generated seeders file will be presented on DatabaseSeeder.php, if you don't want it, you can use **--no-seed** option:

```bash
php artisan seed:generate --table-mode --tables=master_leave_types --no-seed
```
