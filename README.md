# Laravel Passport Client Cookie

This package provides the same cookie based auth that the `CreateFreshApiToken` middleware does, but for `client_credentials`. This is useful when you protect non-user routes, but still want to consume them on the frontend without introducing a proxy. 

_Most of the code contained in this package is taken from [Laravel Passport](https://github.com/laravel/passport) and adapted for this use-case - all credit goes to that repo._

## Installation

```bash
composer require netsells/passport-client-cookie
```

Add to your app.php if not using Laravel 5.5+
```php
    // Other service providers
    Netsells\PassportClientCookie\ServiceProvider::class,
],
```

## Usage

In `Http/Kernel.php`:

Add to your `web` middleware group, probably at the bottom.
```php
\Netsells\PassportClientCookie\Middleware\CreateFreshClientCredentialsApiToken::class,
```

Replace your `CheckClientCredentials` route middleware with the passport client check:

```php
'client' => \Netsells\PassportClientCookie\Middleware\CheckClientCredentials::class,
```