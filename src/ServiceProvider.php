<?php

namespace Netsells\PassportClientCookie;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/config/passport-client-cookie.php' => config_path('passport-client-cookie.php'),
        ]);
    }

     /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/passport-client-cookie.php', 'passport-client-cookie');
    }

}
