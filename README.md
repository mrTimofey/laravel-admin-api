API backend for administration panels.

## Requirements

* PHP 7.1
* Laravel 5

## Install

```bash
composer require mr-timofey/laravel-admin-api
```

Follow installation instructions from
[mr-timofey/laravel-aio-images](https://github.com/mrTimofey/laravel-aio-images),
[mr-timofey/laravel-simple-tokens](https://github.com/mrTimofey/laravel-simple-tokens)
to properly install dependencies.

**For Laravel <= 5.4** add `MrTimofey\LaravelAdminApi\ServiceProvider` to your `app.providers` config.

```bash
php artisan vendor:publish --provider="MrTimofey\LaravelAdminApi\ServiceProvider"
```

Open `config/admin_api.php` for further configuration instructions.

## Frontend

Supported frontend solutions:
* https://github.com/mrTimofey/vue-admin

## Authentication and authorization

This package uses [mr-timofey/laravel-simple-tokens](https://github.com/mrTimofey/laravel-simple-tokens)
by default to maintain authorization logic.
You can change middleware in `admin_api.api_middleware` config and replace default controller with
`app()->bind(\MrTimofey\LaravelAdminApi\Http\Controllers\Auth::class, YourController::class)`

Just set `admin_api.api_middleware` to empty array to remove authorization.