# Laravel Seeder Generator

Generate laravel seeder file from a real data from your database.

## Supported Database

-   [x] MariaDB
-   [x] MySQL
-   [x] PostgreSQL
-   [x] SQL Server
-   [x] SQLite

## Laravel Version Compatibility

I just tested on Laravel 9 and 10.

## Install

```bash
composer require --dev tyghaykal/laravel-seeder-generator
```

## Laravel Setup

Laravel will automatically register the service provider for you, so no more step on this.

## Usage

To generate your seeder file, you can run:

```bash
php artisan seed:generate ModelNamespace
```

Change the `ModelNamespace` into your model Namespace without \App\Models or \App, for example your model namespace is under `\App\Models\Master\Type`, so you just need type `Master\Type`

```bash
php artisan seed:generate Master\Type
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

You can also define which field that you want exclude to seeder file based on the field name with:

```bash
php artisan seed:generate ModelNamespace --ignore-fields="id,name"
```

## Options

| Option          | Description                                                                 |
| --------------- | --------------------------------------------------------------------------- |
| --ids           | The ids to be seeded, cannot be used simultaneously with --ignore-ids       |
| --ignore-ids    | The ids to be ignored, cannot be used simultaneously with --ids             |
| --fields        | The fields to be seeded, cannot be used simultaneously with --ignore-fields |
| --ignore-fields | The fields to be ignored, cannot be used simultaneously with --fields       |
