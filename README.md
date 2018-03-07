# Laravel Passport Client Cookie

This package provides the same cookie based auth that the `CreateFreshApiToken` middleware does, but for `client_credentials`. This is useful when you protect non-user routes, but still want to consume them on the frontend without introducing a proxy. 

_Most of the code contained in this package is taken from [Laravel Passport](https://github.com/laravel/passport) and adapted for this use-case - all credit goes to that repo._