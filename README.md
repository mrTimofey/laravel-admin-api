API backend for administration panels based on [vue-admin-front](https://github.com/mrTimofey/vue-admin) package.

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

## Authentication and authorization

This package uses [mr-timofey/laravel-simple-tokens](https://github.com/mrTimofey/laravel-simple-tokens)
to maintain authentication and authorization logic.

You can change a guard which is used for API by setting a proper `auth:{guard name}` middleware and guard name
in `admin_api.api_middleware` and `admin_api.api_guard` config respectively.

Remove `auth` middleware if you want to disable authorization.

Also you can completely change authentication and authorization logic by rebinding auth controller class:
`app()->bind(\MrTimofey\LaravelAdminApi\Http\Controllers\Auth::class, YourController::class)`
